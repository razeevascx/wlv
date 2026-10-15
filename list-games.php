<!doctype html>
<html lang="en">
  <body>
    <h1>List of ALL my games!!!</h1>
    <?php
      // Connect to database
      include("db.php");
	
      // Run SQL query
      $sql = "SELECT * FROM videogames ORDER BY released_date";
      $results = mysqli_query($mysqli, $sql);
    ?>
    <form action="search-games.php" method="post">
  <input type="text" name="keywords" placeholder="Search">
  <input type="submit" value="Go!">
</form>
<a href="add-game-form.php" class="btn btn-primary">Add a game</a>


    <table>
      <?php while($a_row = mysqli_fetch_assoc($results)):?>
  <tr>
    <td><a href="game-details.php?id=<?=$a_row['game_id']?>"><?=$a_row['game_name']?></a></td>
    <td><?=$a_row['rating']?></td>
   </tr>
<?php endwhile;?>

    </table>
  </body>
