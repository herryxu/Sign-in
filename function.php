<?php
function save_info($table,$action="",$data="",$where=""){
	if(mysql_connects()){
		if( !$table || !$action || !$data){
			exit('no param!');
		}
		if($action == 'insert' ){
			$colum = join(',',array_keys($data));
			$values= join(',',array_map(function($tiem){
				return "'".addslashes($tiem)."'";
			},$data));
			$sql='insert into '.$table."(" .$colum . ') values ('.$values .') ';
		}
		if($action == 'update' ){
			$colum=array_keys($data);
			$values=array_values($data);
			$str='';
			for($i=0;$i<count(array_keys($data));$i++){
				if($colum[$i+1])
					$str.=$colum[$i]."='".$values[$i]."',";
				else
					$str.=$colum[$i]."='".$values[$i]."'";
			}
			$sql=$action." ".$table." set ".$str." where ".$where;
		}
		$result=mysql_query($sql);
		if($result){
			return true;
		}else{
			return false;
		}
	}
}
?>