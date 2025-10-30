<title>coba</title>
<h1>selamat bisa</h1>
<?php
// $nama = array("kari", "ayam", "shihlin");
// echo $nama[0];
// $kota = "jakarta";
// echo $kota;
// echo "Saya suke" .' '.$nama[0] .' ' .$nama[1] .' ' ."di" .' ' .$kota;


// associative
$age = array(
  "Tom" => "33",
  "Gus" => "34",
  "Dony" => "43"
);

echo "Tom is {$age['Tom']} years old..<br>";

// foreach ($age as $name => $years) {
//   echo "$name is $years years old.<br>";
// }

$fruit = array(
  array("Orange", 13, 1),
  array("Banana", 5, 10),
  array("Watermelon", 7, 0)
);

echo $fruit[0][0] . " sold: " . $fruit[0][1] . ", in stock: " . $fruit[0][2] . "<br>";
foreach ($fruit as $item) {
  echo $item[0] . " sold: " . $item[1] . ", in stock: " . $item[2] . "<br>";
}
?>