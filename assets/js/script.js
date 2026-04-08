document.addEventListener("DOMContentLoaded", function () {
  var filterButtons = document.querySelectorAll(".genreFilterBtn");
  var bookItems = document.querySelectorAll(".bookItem");
  var detailButtons = document.querySelectorAll(".viewDetailsBtn");
  var searchInput = document.getElementById("bookSearchInput");
  var searchByInputs = document.querySelectorAll('input[name="searchBy"]');
  var priceFilter = document.getElementById("priceFilter");
  var availabilityFilter = document.getElementById("availabilityFilter");
  var resultsMeta = document.getElementById("searchResultsMeta");
  var noResultsMessage = document.getElementById("noResultsMessage");
  var modalOverlay = document.getElementById("bookModalOverlay");
  var modalClose = document.getElementById("bookModalClose");
  var modalTitle = document.getElementById("bookModalTitle");
  var modalDescription = document.getElementById("bookModalDescription");
  var modalAuthor = document.getElementById("bookModalAuthor");
  var modalPrice = document.getElementById("bookModalPrice");
  var modalStock = document.getElementById("bookModalStock");
  var activeGenre = "all";

  function getSelectedSearchBy() {
    var selected = document.querySelector('input[name="searchBy"]:checked');

    return selected ? selected.value : "title";
  }

  function applyBookFilters() {
    if (!bookItems.length) {
      return;
    }

    var searchBy = getSelectedSearchBy();
    var searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : "";
    var selectedPriceRange = priceFilter ? priceFilter.value : "all";
    var selectedAvailability = availabilityFilter ? availabilityFilter.value : "all";
    var visibleCount = 0;

    bookItems.forEach(function (item) {
      var itemGenre = item.getAttribute("data-genre") || "";
      var itemTitle = item.getAttribute("data-title") || "";
      var itemAuthor = item.getAttribute("data-author") || "";
      var itemPrice = parseFloat(item.getAttribute("data-price") || "0");
      var itemStockCount = parseInt(item.getAttribute("data-stock-count") || "0", 10);
      var searchSource = "";
      var matchesGenre = activeGenre === "all" || itemGenre === activeGenre;
      var matchesPrice = true;
      var matchesAvailability = true;

      if (searchBy === "author") {
        searchSource = itemAuthor;
      } else if (searchBy === "genre") {
        searchSource = itemGenre;
      } else {
        searchSource = itemTitle;
      }

      var matchesSearch = !searchTerm || searchSource.toLowerCase().indexOf(searchTerm) !== -1;

      if (selectedPriceRange === "under-16") {
        matchesPrice = itemPrice < 16;
      } else if (selectedPriceRange === "16-20") {
        matchesPrice = itemPrice >= 16 && itemPrice <= 20;
      } else if (selectedPriceRange === "over-20") {
        matchesPrice = itemPrice > 20;
      }

      if (selectedAvailability === "in-stock") {
        matchesAvailability = itemStockCount > 0;
      } else if (selectedAvailability === "low-stock") {
        matchesAvailability = itemStockCount > 0 && itemStockCount <= 10;
      }

      var shouldShow = matchesGenre && matchesSearch && matchesPrice && matchesAvailability;

      item.classList.toggle("isHidden", !shouldShow);

      if (shouldShow) {
        visibleCount += 1;
      }
    });

    if (resultsMeta) {
      resultsMeta.textContent = "Showing " + visibleCount + " book" + (visibleCount === 1 ? "" : "s");
    }

    if (noResultsMessage) {
      noResultsMessage.classList.toggle("isHidden", visibleCount !== 0);
    }
  }

  if (filterButtons.length && bookItems.length) {
    filterButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        activeGenre = button.getAttribute("data-filter") || "all";

        filterButtons.forEach(function (btn) {
          btn.classList.remove("activeGenre");
        });

        button.classList.add("activeGenre");
        applyBookFilters();
      });
    });
  }

  if (searchInput) {
    searchInput.addEventListener("input", applyBookFilters);
  }

  if (searchByInputs.length) {
    searchByInputs.forEach(function (input) {
      input.addEventListener("change", applyBookFilters);
    });
  }

  if (priceFilter) {
    priceFilter.addEventListener("change", applyBookFilters);
  }

  if (availabilityFilter) {
    availabilityFilter.addEventListener("change", applyBookFilters);
  }

  function closeModal() {
    if (!modalOverlay) {
      return;
    }

    modalOverlay.classList.remove("isOpen");
    modalOverlay.setAttribute("aria-hidden", "true");
    modalOverlay.hidden = true;
    document.body.style.overflow = "";
  }

  if (detailButtons.length && modalOverlay) {
    detailButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        modalTitle.textContent = button.getAttribute("data-title") || "Book Details";
        modalDescription.textContent = button.getAttribute("data-description") || "";
        modalAuthor.textContent = button.getAttribute("data-author") || "";
        modalPrice.textContent = button.getAttribute("data-price") || "";
        modalStock.textContent = button.getAttribute("data-stock") || "";

        modalOverlay.hidden = false;
        modalOverlay.classList.add("isOpen");
        modalOverlay.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
      });
    });

    modalOverlay.addEventListener("click", function (event) {
      if (event.target === modalOverlay) {
        closeModal();
      }
    });
  }

  if (modalClose) {
    modalClose.addEventListener("click", closeModal);
  }

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeModal();
    }
  });

  applyBookFilters();
});
