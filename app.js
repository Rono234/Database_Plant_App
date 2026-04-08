const button = document.getElementById("action-btn");
const message = document.getElementById("message");

button?.addEventListener("click", () => {
  message.textContent = "Nice. JavaScript is connected and working.";
});

/* STAR RATING INTERACTIVITY */
const ratingPickers = document.querySelectorAll(".rating-input");

ratingPickers.forEach((picker) => {
  const stars = picker.querySelectorAll("span, button");
  const targetSelector = picker.getAttribute("data-target");
  const targetInput = targetSelector ? document.querySelector(targetSelector) : null;
  let selectedValue = targetInput ? Number(targetInput.value || 0) : 0;

  const renderStars = (value) => {
    stars.forEach((s) => {
      const starValue = Number(s.getAttribute("data-value") || 0);
      const isActive = starValue <= value;
      s.classList.toggle("active", isActive);
      s.textContent = isActive ? "★" : "☆";
    });
  };

  renderStars(selectedValue);

  stars.forEach((star) => {
    star.addEventListener("mouseenter", () => {
      const value = Number(star.getAttribute("data-value") || 0);
      renderStars(value);
    });

    star.addEventListener("mouseleave", () => {
      renderStars(selectedValue);
    });

    star.addEventListener("click", () => {
      const value = Number(star.getAttribute("data-value") || 0);
      selectedValue = value;
      renderStars(selectedValue);

      if (targetInput) {
        targetInput.value = String(selectedValue);
      }
    });
  });

  picker.addEventListener("mouseleave", () => {
    renderStars(selectedValue);
  });
});

const commentForm = document.querySelector(".comment-form");
commentForm?.addEventListener("submit", (event) => {
  const ratingInput = document.getElementById("comment_rating");
  if (!ratingInput || Number(ratingInput.value) < 1 || Number(ratingInput.value) > 5) {
    event.preventDefault();
    window.alert("Please choose a rating from 1 to 5 stars.");
  }
});