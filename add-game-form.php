<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bootstrap demo</title>
<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
rel="stylesheet" integrity="sha384-
sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
crossorigin="anonymous">
</head>
<body>
<div class="container">
<h1>Add a videogame</h1>
<form action="add-game.php" method="post">
<div class="mb-3">
<label for="GameName" class="form-label">Game name</label>
<input type="text" class="form-control" id="GameName" name="GameName">
</div>
<div class="mb-3">
<label for="GameDescription" class="form-label">Description</label>
<textarea class="form-control" id="GameDescription"
name="GameDescription" rows="5"></textarea>
</div>
<div class="mb-3">
<label for="DateReleased" class="form-label">Date released</label>
<input type="date" class="form-control" id="DateReleased"
name="DateReleased">
</div>
<div class="mb-3">
<label for="GameRating" class="form-label">Rating</label>
<input type="number" class="form-control" id="GameRating"
name="GameRating">
</div>
<input type="submit" class="btn btn-primary" value="Add game">
</form>
</div>
</body>
</html>