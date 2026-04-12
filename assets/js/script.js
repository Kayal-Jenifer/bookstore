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
  var bookForm = document.getElementById("bookForm");
  var coverImageInput = document.getElementById("coverImageInput");
  var bookCropTool = document.getElementById("bookCropTool");
  var cropFrame = document.getElementById("bookCropFrame");
  var cropPreview = document.getElementById("bookCropPreview");
  var cropZoomOutBtn = document.getElementById("cropZoomOutBtn");
  var cropZoomInBtn = document.getElementById("cropZoomInBtn");
  var cropResetBtn = document.getElementById("cropResetBtn");
  var originalCoverImage = null;
  var originalCoverName = "";
  var cropState = {
    baseScale: 1,
    zoom: 1,
    offsetX: 0,
    offsetY: 0,
    dragging: false,
    startX: 0,
    startY: 0,
    startOffsetX: 0,
    startOffsetY: 0
  };

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

  function clampCropOffsets() {
    if (!cropFrame || !originalCoverImage) {
      return;
    }

    var frameWidth = cropFrame.clientWidth;
    var frameHeight = cropFrame.clientHeight;
    var scaledWidth = originalCoverImage.width * cropState.baseScale * cropState.zoom;
    var scaledHeight = originalCoverImage.height * cropState.baseScale * cropState.zoom;
    var maxOffsetX = Math.max(0, (scaledWidth - frameWidth) / 2);
    var maxOffsetY = Math.max(0, (scaledHeight - frameHeight) / 2);

    cropState.offsetX = Math.min(maxOffsetX, Math.max(-maxOffsetX, cropState.offsetX));
    cropState.offsetY = Math.min(maxOffsetY, Math.max(-maxOffsetY, cropState.offsetY));
  }

  function updateCropPreview() {
    if (!cropPreview || !cropFrame || !originalCoverImage) {
      return;
    }

    clampCropOffsets();
    cropPreview.style.transform =
      "translate(calc(-50% + " + cropState.offsetX + "px), calc(-50% + " + cropState.offsetY + "px)) scale(" + (cropState.baseScale * cropState.zoom) + ")";
  }

  function resetCropState() {
    if (!cropFrame || !originalCoverImage) {
      return;
    }

    cropState.baseScale = Math.max(
      cropFrame.clientWidth / originalCoverImage.width,
      cropFrame.clientHeight / originalCoverImage.height
    );
    cropState.zoom = 1;
    cropState.offsetX = 0;
    cropState.offsetY = 0;
    updateCropPreview();
  }

  if (coverImageInput && bookCropTool && cropPreview && cropFrame) {
    coverImageInput.addEventListener("change", function () {
      var file = coverImageInput.files && coverImageInput.files[0];

      if (!file) {
        bookCropTool.hidden = true;
        cropPreview.removeAttribute("src");
        originalCoverImage = null;
        return;
      }

      originalCoverName = file.name;
      var reader = new FileReader();

      reader.onload = function (event) {
        originalCoverImage = new Image();
        originalCoverImage.onload = function () {
          cropPreview.src = event.target.result;
          bookCropTool.hidden = false;
          resetCropState();
        };
        originalCoverImage.src = event.target.result;
      };

      reader.readAsDataURL(file);
    });

    cropFrame.addEventListener("pointerdown", function (event) {
      if (!originalCoverImage) {
        return;
      }

      cropState.dragging = true;
      cropState.startX = event.clientX;
      cropState.startY = event.clientY;
      cropState.startOffsetX = cropState.offsetX;
      cropState.startOffsetY = cropState.offsetY;
      cropFrame.classList.add("isDragging");
      cropFrame.setPointerCapture(event.pointerId);
    });

    cropFrame.addEventListener("pointermove", function (event) {
      if (!cropState.dragging) {
        return;
      }

      cropState.offsetX = cropState.startOffsetX + (event.clientX - cropState.startX);
      cropState.offsetY = cropState.startOffsetY + (event.clientY - cropState.startY);
      updateCropPreview();
    });

    function stopCropDragging(event) {
      if (!cropState.dragging) {
        return;
      }

      cropState.dragging = false;
      cropFrame.classList.remove("isDragging");

      if (event && typeof event.pointerId !== "undefined") {
        cropFrame.releasePointerCapture(event.pointerId);
      }
    }

    cropFrame.addEventListener("pointerup", stopCropDragging);
    cropFrame.addEventListener("pointercancel", stopCropDragging);
  }

  if (cropZoomInBtn) {
    cropZoomInBtn.addEventListener("click", function () {
      if (!originalCoverImage) {
        return;
      }

      cropState.zoom = Math.min(3, cropState.zoom + 0.15);
      updateCropPreview();
    });
  }

  if (cropZoomOutBtn) {
    cropZoomOutBtn.addEventListener("click", function () {
      if (!originalCoverImage) {
        return;
      }

      cropState.zoom = Math.max(1, cropState.zoom - 0.15);
      updateCropPreview();
    });
  }

  if (cropResetBtn) {
    cropResetBtn.addEventListener("click", function () {
      resetCropState();
    });
  }

  if (bookForm && coverImageInput) {
    bookForm.addEventListener("submit", function (event) {
      if (!originalCoverImage || !coverImageInput.files || !coverImageInput.files.length) {
        return;
      }

      event.preventDefault();

      var frameWidth = 440;
      var frameHeight = 640;
      var canvas = document.createElement("canvas");
      var context = canvas.getContext("2d");
      var scale = cropState.baseScale * cropState.zoom;
      var drawWidth = originalCoverImage.width * scale;
      var drawHeight = originalCoverImage.height * scale;
      var drawX = (frameWidth - drawWidth) / 2 + cropState.offsetX * 2;
      var drawY = (frameHeight - drawHeight) / 2 + cropState.offsetY * 2;

      canvas.width = frameWidth;
      canvas.height = frameHeight;
      context.fillStyle = "#f7efe7";
      context.fillRect(0, 0, frameWidth, frameHeight);
      context.drawImage(originalCoverImage, drawX, drawY, drawWidth, drawHeight);

      canvas.toBlob(function (blob) {
        if (!blob) {
          bookForm.submit();
          return;
        }

        var croppedFile = new File([blob], originalCoverName || "cropped-cover.png", {
          type: "image/png"
        });
        var dataTransfer = new DataTransfer();
        dataTransfer.items.add(croppedFile);
        coverImageInput.files = dataTransfer.files;
        bookForm.submit();
      }, "image/png");
    });
  }

  applyBookFilters();
});
