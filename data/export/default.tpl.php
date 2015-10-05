<?php
header('Content-Disposition: attachment; filename="shomyo-items.csv"');
header('Content-Type: text/csv; charset=UTF-8');
?>
datetime,title,sourcetitle,author,uid,link
<?php foreach( $this->items as $i ){
echo $i['datetime'].',"'.$i['title'].'","'.$i['sourcetitle'].'","'.$i['author'].'","'.$i['uid'].'","'.$i['link']."\"\n";
}?>