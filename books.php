<?php
include 'includes/db.php';

$booksResult = $conn->query(
    "SELECT books.*, authors.name AS author_name
    FROM books
    INNER JOIN authors ON authors.author_id = books.author_id
    ORDER BY books.title ASC"
);
$books = $booksResult ? $booksResult->fetch_all(MYSQLI_ASSOC) : [];

function getBookAudience(string $genre): string
{
    $normalizedGenre = strtolower(trim($genre));

    if (str_contains($normalizedGenre, 'kid') || str_contains($normalizedGenre, 'child')) {
        return 'kids';
    }

    if (str_contains($normalizedGenre, 'teen') || str_contains($normalizedGenre, 'young adult')) {
        return 'teens';
    }

    if ($normalizedGenre === 'kids' || $normalizedGenre === 'teens' || $normalizedGenre === 'adults') {
        return $normalizedGenre;
    }

    return 'adults';
}

include 'includes/header.php';
?>

<main>
  <section class="heroSection text-center text-white d-flex align-items-center">
    <div class="container">
      <h1 class="display-6 fw-bold">Explore Your Next Great Read</h1>
      <p class="lead mb-4">Explore our extensive collection of books across all genres. Whether you're looking for the latest bestseller or a timeless classic, we have something for every reader.</p>

      <nav class="genreFilterNav">
        <button type="button" class="genreFilterBtn activeGenre" data-filter="all">All Books</button>
        <button type="button" class="genreFilterBtn" data-filter="kids">Kids</button>
        <button type="button" class="genreFilterBtn" data-filter="teens">Teens</button>
        <button type="button" class="genreFilterBtn" data-filter="adults">Adults</button>
      </nav>

      <section class="searchFilterPanel" aria-label="Search and filter books">
        <div class="searchFilterControls">
          <div class="searchModeGroup" role="radiogroup" aria-label="Search books by">
            <label class="searchModeOption">
              <input type="radio" name="searchBy" value="title" checked>
              <span>Title</span>
            </label>
            <label class="searchModeOption">
              <input type="radio" name="searchBy" value="author">
              <span>Author</span>
            </label>
          </div>

          <div class="searchFilterFields">
            <label class="searchField">
              <span>Search term</span>
              <input type="search" id="bookSearchInput" placeholder="Type to search">
            </label>

            <label class="searchField">
              <span>Price range</span>
              <select id="priceFilter">
                <option value="all">All prices</option>
                <option value="under-16">$10 to $16</option>
                <option value="16-20">$16 to $20</option>
                <option value="over-20">above $20</option>
              </select>
            </label>

            <label class="searchField">
              <span>Availability</span>
              <select id="availabilityFilter">
                <option value="all">All books</option>
                <option value="in-stock">In stock</option>
                <option value="low-stock">Low stock (10 or less)</option>
              </select>
            </label>
          </div>
        </div>

        <p id="searchResultsMeta" class="searchResultsMeta mt-3">Showing <?php echo count($books); ?> book<?php echo count($books) === 1 ? '' : 's'; ?></p>
      </section>
    </div>
  </section>

  <section class="booksListingSection">
    <div class="container">
      <div class="booksGrid row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4" id="booksGrid">
        <?php if (!$books): ?>
          <div class="col-12">
            <div class="emptyBooksState">
              <h3>No books are available yet.</h3>
              <p class="mb-0">Log in to the admin dashboard to create your first book record.</p>
            </div>
          </div>
        <?php endif; ?>

        <?php foreach ($books as $index => $book): ?>
          <?php
          $audience = getBookAudience($book["genre"]);
          $imageNumber = ($index % 4) + 1;
          $coverPath = "assets/images/book" . $imageNumber . ".jpg";
          $description = $book["title"] . " is a " . strtolower($book["genre"]) . " title by " . $book["author_name"] . ", published by " . $book["publisher"] . ".";
          ?>
          <div
            class="col bookItem"
            data-genre="<?php echo htmlspecialchars($audience); ?>"
            data-title="<?php echo htmlspecialchars($book["title"]); ?>"
            data-author="<?php echo htmlspecialchars($book["author_name"]); ?>"
            data-price="<?php echo htmlspecialchars((string) $book["price"]); ?>"
            data-stock-count="<?php echo (int) $book["stock"]; ?>"
          >
            <div class="card bookCard h-100">
              <img src="<?php echo htmlspecialchars($coverPath); ?>" class="card-img-top bookCover" alt="<?php echo htmlspecialchars($book["title"]); ?>">
              <div class="card-body bookCardBody">
                <span class="bookGenreTag"><?php echo htmlspecialchars($book["genre"]); ?></span>
                <h5 class="card-title"><?php echo htmlspecialchars($book["title"]); ?></h5>
                <p class="card-text">By <?php echo htmlspecialchars($book["author_name"]); ?>. Published by <?php echo htmlspecialchars($book["publisher"]); ?>.</p>
                <div class="bookCardActions">
                  <button
                    type="button"
                    class="btn btn-primary viewDetailsBtn"
                    data-title="<?php echo htmlspecialchars($book["title"]); ?>"
                    data-author="<?php echo htmlspecialchars($book["author_name"]); ?>"
                    data-price="$<?php echo number_format((float) $book["price"], 2); ?>"
                    data-stock="<?php echo (int) $book["stock"]; ?> copies available"
                    data-description="<?php echo htmlspecialchars($description); ?>"
                  >
                    View Details
                  </button>
                  <a href="contact.php?book_id=<?php echo (int) $book["book_id"]; ?>" class="btn btn-outline-warning">Order Book</a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div id="noResultsMessage" class="emptyBooksState isHidden mt-4">
        <h3>No books match those filters.</h3>
        <p class="mb-0">Try adjusting the search term, genre, price range, or availability.</p>
      </div>
    </div>
  </section>

  <div class="bookModalOverlay" id="bookModalOverlay" aria-hidden="true" hidden>
    <div class="bookModalBox" role="dialog" aria-modal="true" aria-labelledby="bookModalTitle">
      <button type="button" class="bookModalClose" id="bookModalClose" aria-label="Close details">&times;</button>
      <div class="bookModalGlow"></div>
      <span class="bookModalEyebrow">Book Details</span>
      <h3 id="bookModalTitle">Book Details</h3>
      <div class="bookModalContent">
        <p id="bookModalDescription"></p>
        <div class="bookMetaList">
          <div class="bookMetaRow"><span>Author</span><strong id="bookModalAuthor"></strong></div>
          <div class="bookMetaRow"><span>Price</span><strong id="bookModalPrice"></strong></div>
          <div class="bookMetaRow"><span>Available</span><strong id="bookModalStock"></strong></div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
