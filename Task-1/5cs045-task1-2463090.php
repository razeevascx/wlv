
<?php
// Database connection
$mysqli = null;
$dbAvailable = false;
$movies = [];
$message = "";
$added = isset($_GET["added"]);

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $mysqli = new mysqli(
        "localhost",
        "2463090",
        "2463090",
        "db2463090"
    );

    $mysqli->set_charset("utf8mb4");
    $dbAvailable = true;

} catch (Throwable $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $message = "Database is unavailable. Trying your saved movies.";
}

// Add a movie
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $movie_name = trim($_POST["Movie_name"] ?? "");
    $genre = trim($_POST["Genre"] ?? "");
    $price = $_POST["Price"] ?? "";
    $release_date = $_POST["release_date"] ?? "";

    if (
        !$dbAvailable
    ) {
        $message = "Cannot add a movie while the database is unavailable. Your saved movies are shown below.";
    } elseif (
        $movie_name === "" ||
        strlen($movie_name) > 100 ||
        $genre === "" ||
        strlen($genre) > 20 ||
        !is_numeric($price) ||
        !is_finite((float)$price) ||
        (float)$price < 0
    ) {
        $message = "Please enter valid movie details.";
    } elseif (
        $release_date !== "" &&
        (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $release_date) ||
            !checkdate(
                (int)substr($release_date, 5, 2),
                (int)substr($release_date, 8, 2),
                (int)substr($release_date, 0, 4)
            )
        )
    ) {
        $message = "Please enter a valid release date.";
    } else {
        try {
            $sql = "INSERT INTO movies
                    (Movie_name, Genre, Price, `Date of release`)
                    VALUES (?, ?, ?, ?)";

            $stmt = $mysqli->prepare($sql);

            $movie_price = (float)$price;
            $date = $release_date !== "" ? $release_date : null;

            $stmt->bind_param(
                "ssds",
                $movie_name,
                $genre,
                $movie_price,
                $date
            );

            $stmt->execute();
            $stmt->close();

            // Reload the database records and refresh localStorage.
            header("Location: 5cs045-task1-2463090.php?added=1");
            exit;

        } catch (Throwable $e) {
            error_log("Movie insert failed: " . $e->getMessage());
            $message = "Unable to add the movie. Please try again.";
        }
    }
}

// Retrieve movies from the database
if ($dbAvailable) {
    try {
        $sql = "SELECT Movie_id, Movie_name, Genre, Price,
                       `Date of release` AS release_date
                FROM movies
                ORDER BY Movie_id DESC";

        $result = $mysqli->query($sql);
        $movies = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

    } catch (Throwable $e) {
        error_log("Movie retrieval failed: " . $e->getMessage());
        $dbAvailable = false;
        $message = "Unable to retrieve movies from the database. Trying your saved movies.";
    }
}

if ($mysqli !== null) {
    $mysqli->close();
}

// Encode the actual database records for JavaScript.
$moviesJson = json_encode(
    $movies,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP |
    JSON_INVALID_UTF8_SUBSTITUTE
);

if ($moviesJson === false) {
    $moviesJson = "[]";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Movie Library</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="5cs045-task1-2463090.php">
            Movie Library
        </a>

        <button
            type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#addMovieModal"
        >
            + Add Movie
        </button>
    </div>
</nav>

<main class="container py-4">

    <?php if ($added): ?>
        <div class="alert alert-success alert-dismissible fade show"
             role="alert">
            Movie added successfully!
            <button type="button" class="btn-close"
                    data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($message !== ""): ?>
        <div class="alert alert-warning" role="alert">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <!-- Data source status -->
    <div id="cacheStatus"
         class="alert alert-secondary py-2"
         role="status"
         aria-live="polite">
        Loading movies...
    </div>

    <!-- Movie count -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">All Movies</h2>

        <span id="movieCount" class="badge text-bg-secondary">
            0 movies
        </span>
    </div>

    <!-- JavaScript renders database or cached records here -->
    <div class="row g-4" id="movieGrid">
        <div class="col-12">
            <div class="alert alert-info">
                Loading movie collection...
            </div>
        </div>
    </div>

</main>

<!-- Add Movie Modal -->
<div
    class="modal fade"
    id="addMovieModal"
    tabindex="-1"
    aria-labelledby="addMovieModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h2 class="modal-title fs-5" id="addMovieModalLabel">
                    Add a New Movie
                </h2>

                <button type="button" class="btn-close"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="5cs045-task1-2463090.php">
                <div class="modal-body">

                    <div class="mb-3">
                        <label for="Movie_name" class="form-label">
                            Movie Name
                        </label>
                        <input type="text" class="form-control"
                               id="Movie_name" name="Movie_name"
                               maxlength="100" required>
                    </div>

                    <div class="mb-3">
                        <label for="Genre" class="form-label">
                            Genre
                        </label>
                        <input type="text" class="form-control"
                               id="Genre" name="Genre"
                               maxlength="20" required>
                    </div>

                    <div class="mb-3">
                        <label for="Price" class="form-label">
                            Price
                        </label>
                        <input type="number" class="form-control"
                               id="Price" name="Price"
                               min="0" step="any" required>
                    </div>

                    <div class="mb-3">
                        <label for="release_date" class="form-label">
                            Date of Release
                        </label>
                        <input type="date" class="form-control"
                               id="release_date" name="release_date">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Save Movie
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Actual records retrieved by PHP from MySQL.
const serverMovies = <?= $moviesJson ?>;
const serverAvailable = <?= $dbAvailable ? "true" : "false" ?>;

const CACHE_KEY = "movieLibraryCache";

const movieGrid = document.getElementById("movieGrid");
const movieCount = document.getElementById("movieCount");
const cacheStatus = document.getElementById("cacheStatus");

// Render records safely without inserting movie values as HTML.
function renderMovies(movies) {
    movieGrid.replaceChildren();

    movieCount.textContent =
        movies.length + (movies.length === 1 ? " movie" : " movies");

    if (movies.length === 0) {
        const column = document.createElement("div");
        column.className = "col-12";

        const alert = document.createElement("div");
        alert.className = "alert alert-info";
        alert.textContent = "No movies found. Add your first movie.";

        column.appendChild(alert);
        movieGrid.appendChild(column);
        return;
    }

    movies.forEach(movie => {
        const column = document.createElement("div");
        column.className = "col-12 col-md-6 col-lg-4";

        const card = document.createElement("article");
        card.className = "card h-100 border-0 shadow-sm";

        const body = document.createElement("div");
        body.className = "card-body";

        const headingRow = document.createElement("div");
        headingRow.className =
            "d-flex justify-content-between align-items-start gap-2 mb-3";

        const title = document.createElement("h3");
        title.className = "card-title h5 mb-0";
        title.textContent = movie.Movie_name ?? "";

        const idBadge = document.createElement("span");
        idBadge.className = "badge text-bg-primary";
        idBadge.textContent = "#" + movie.Movie_id;

        headingRow.append(title, idBadge);
        body.appendChild(headingRow);

        const details = [
            ["Genre", movie.Genre ?? ""],
            ["Price", Number(movie.Price).toFixed(2)],
            ["Release date", movie.release_date || "Not specified"]
        ];

        details.forEach(([label, value]) => {
            const paragraph = document.createElement("p");
            paragraph.className = "mb-2";

            const labelElement = document.createElement("span");
            labelElement.className = "text-muted";
            labelElement.textContent = label + ": ";

            const valueElement = document.createElement("span");
            valueElement.textContent = String(value);

            paragraph.append(labelElement, valueElement);
            body.appendChild(paragraph);
        });

        card.appendChild(body);
        column.appendChild(card);
        movieGrid.appendChild(column);
    });
}

// Load the cached records safely.
function getCachedMovies() {
    try {
        const cached = localStorage.getItem(CACHE_KEY);

        if (cached === null) {
            return null;
        }

        const movies = JSON.parse(cached);

        if (
            !Array.isArray(movies) ||
            !movies.every(movie =>
                movie &&
                typeof movie === "object" &&
                "Movie_id" in movie &&
                "Movie_name" in movie &&
                "Genre" in movie &&
                "Price" in movie
            )
        ) {
            return null;
        }

        return movies;
    } catch (error) {
        console.warn("Unable to read movie cache.", error);
        return null;
    }
}

// Save the real database records, not data reconstructed from card text.
function saveCachedMovies(movies) {
    try {
        localStorage.setItem(CACHE_KEY, JSON.stringify(movies));
        return true;
    } catch (error) {
        console.warn("Unable to save movie cache.", error);
        return false;
    }
}

// Select the best available source.
if (serverAvailable) {
    // The database is authoritative, even if it returns zero movies.
    renderMovies(serverMovies);
    saveCachedMovies(serverMovies);

    cacheStatus.className = "alert alert-success py-2";
    cacheStatus.textContent = "Showing the latest movies from the database.";
} else {
    const cachedMovies = getCachedMovies();

    if (cachedMovies !== null) {
        renderMovies(cachedMovies);

        cacheStatus.className = "alert alert-warning py-2";
        cacheStatus.textContent =
            "Database unavailable. Showing saved movies from this browser; these records may be outdated.";
    } else {
        renderMovies([]);

        cacheStatus.className = "alert alert-danger py-2";
        cacheStatus.textContent =
            "Database unavailable and no saved movie cache was found in this browser.";
    }
}

// Reopen the modal if server-side validation or insertion failed.
<?php if ($message !== "" && $_SERVER["REQUEST_METHOD"] === "POST"): ?>
document.addEventListener("DOMContentLoaded", function () {
    const modalElement = document.getElementById("addMovieModal");
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
});
<?php endif; ?>
</script>

</body>
</html>
