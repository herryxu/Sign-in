<?php
/**
 * Created by PhpStorm.
 * User: xu
 * Date: 2015/5/25 0025 17:22
 */
use Think\Model;

class SigninModel extends Model
{

	/*
	 * 构造方法
	 * gaohaifeng
	 */
	public function __construct ()
	{
		parent::__construct ();


	}
	
	/*
	* 保存每日签到数据
	*/
	public function saveSigndata($table,$data){
		$data['addtime']=time();

		$colum = join(',',array_keys($data));
		 $values= join(',',array_map(function($item){
		 	if(is_int($item))
		 		return $item;
		 	else
		 		return "'".addslashes($item)."'";
		 },$data));
		$sql="insert into ".$table." (" .$colum . ') values ('.$values .') ';

		$result=$this->execute($sql);

		if($result){
			return $data['addtime'];
		}
		
	}

	/**
	* 查询用户当日签到名次
	*
	*
	*/
	public function getSignOrder($table,$guid,$zoneid){
		$this->log->info ('input args:'.func_num_args ().json_encode (func_get_args ()));

		if (!$table)
		{
			return 0;
		}
		$time=get_timestramp();		
		$sql="select count(*) as count from `{$table}` where addtime> '$time' ";
		$result=$this->query($sql);
		return $result[0]['count']?(int)$result[0]['count']:1;
	}
	/**
	*
	*查询用户某月签到
	* $guid 用户id
	* $year   年份
	* $month 月份
	*/
	public function getUserSignOneMonth($table,$guid,$year,$month,$zoneid=''){

		$days= date("t",mktime(0,0,0,$month,1,$year)); 
		$firsttimes=strtotime($year.'-'.$month.'-1 00:00:00');
		$endtimes=strtotime($year.'-'.$month.'-'.$days.' 00:00:00');
		$where='guid ='.$guid." AND addtime>='".$firsttimes."' AND addtime<='".$endtimes."'";
		if($zoneid){
			$where.="AND zoneid='{$zoneid}'";
		}
		
		$sql="select * from `{$table}` where ".$where." ORDER BY addtime ASC";
		$result=$this->query($sql);
		foreach ($result as $k => $v) {
			$signdays['day'][]=(int)date('d',$v['addtime']);
		}
		return $signdays?$signdays:''	;
	}

	/*
	* 判断是否已签到
	*	$guid 用户ID 	必
	*	$type 类型ID 	必
	*	$times  某天时间戳 非 默认当天
	*	$zoneid 所属圈子ID	非
	*/
	public function is_Sign($table,$guid,$times,$zoneid,$in=0){
		
		$year=date('Y',$times);
		$month=date('m',$times);
		$days=date('d',$times);
		$firsttimes=strtotime($year.'-'.$month.'-'.$days.' 00:00:00');
		$endtimes=strtotime($year.'-'.$month.'-'.($days+1).' 00:00:00');
		$where='guid ='.$guid." AND addtime>='".$firsttimes."' AND addtime<='".$endtimes."'";
		if($zoneid){
			$where.="AND zoneid='{$zoneid}'";
		}
		$sql="select * from `{$table}` where ".$where.' ORDER BY addtime DESC limit 1';
		$result=$this->query($sql);
		return $result ? $result:''	;
	}
		/*
	* 	判断是否连续签到
	*	$guid 用户ID 	必
	*	$type 类型ID 	必
	*	$times  某天时间戳 非 默认当天
	*	$zoneid 所属圈子ID	非
	*/
	public function is_contiunesign($table,$guid,$times,$zoneid)
	{
		if(!$times){
			$times =time();
		}	
		$where='guid ='.$guid." AND addtime<='".$times."'";
		if($zoneid){
			$where.=" AND zoneid='{$zoneid}'";
		}
		$where.=" ORDER BY addtime DESC limit 7 ";
		$sql="select addtime,prize from `{$table}` where ".$where;

		$result=$this->query($sql);
		$i=0;
		$tdays=array(4,6,9,11);
		if(count($result)>1){
			$days[0]['y']=(int)date('Y',$times);		
			$days[0]['m']=(int)date('m',$times);
			$days[0]['d']=(int)date('d',$times);	
			$key = 1;			
			foreach ($result as $k => $v) {
				$days[$key]['y']=(int)date('Y',$v['addtime']);		
				$days[$key]['m']=(int)date('m',$v['addtime']);
				$days[$key]['d']=(int)date('d',$v['addtime']);
				$key++;
			}

			if($limit == 6){
				$day['y']=date('Y',$times);
				$day['m']=date('m',$times);
				$day['d']=date('d',$times);
				if($day['d'] != $days[0]['d']){
					array_unshift($days,$day);
					array_pop($days);
				}
				
			}
			while($i<count($days))
			{

				if($days[$i]['y']-$days[$i+1]['y'] >=2)
				{  //相差超过两年
					break;
				}
				else if($days[$i]['y']-$days[$i+1]['y'] ==1)
				{ //跨年的情况

					if($days[$i]['m'] !=1 || $days[$i+1]['m'] !=12){  //跨年的月份
						break;
					}else if($days[$i]['d'] !=1 || $days[$i+1]['d'] !=31){ //跨年的日期
						break;
					}
				
				}
				else
				{  //同一年的情况

					if($days[$i]['m'] - $days[$i+1]['m']>1){  //相差超过一个月
						break;
					}else if($days[$i]['m'] - $days[$i+1]['m']==1 ){ 
						if($days[$i]['d']!=1 || $days[$i+1]['d']<28){ //判断天数是否符合
							break;
						}else{
							if($days[$i+1]['m'] ==2){
								if(check_year($days[$i]['m']) && $days[$i+1]['d'] !=29){
									break;
								}else if($days[$i+1]['d'] !=28){
									break;
								}
							}else{
								if( in_array($days[$i+1]['m'],$tdays) && $days[$i+1]['d'] !=30){
									break;
								}else if($days[$i+1]['d'] !=31){
									break;
								}
							}
						}
					
					}else {
						if($days[$i]['d']- $days[$i+1]['d'] >1 ){	
							break;
						}
					}
				}
				$i++;
			}

		}else if(count($result) == 1 && $result[0]['addtime'] >= get_lasttimestramp()){
			$i = 1;
		}
		$flag = 0;
		foreach ($result as $k => $v) {
			if($v['prize'] == 12){
				$flag = $k;
				break;
			}
		}
		if($flag){
			$i = $flag;
		}else if($i == 7){
			$i =6;
		}
		return $i ? $i:0;
		
	}

}
