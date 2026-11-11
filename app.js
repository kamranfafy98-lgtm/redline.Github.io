
const formatPrice = (price) =>
  Number(price).toLocaleString("fa-IR") + " تومان";

// فیلتر دوره‌ها و محصولات
function setupFilter(searchId, categoryId, gridId, emptyId) {
  const search = document.getElementById(searchId);
  const category = document.getElementById(categoryId);
  const grid = document.getElementById(gridId);
  const empty = document.getElementById(emptyId);

  if (!search || !category || !grid) return;

  function filterItems() {
    const query = search.value.trim().toLowerCase();
    const selected = category.value;
    let visible = 0;

    grid.querySelectorAll(".item-card").forEach(card => {
      const matchesText = card.textContent.toLowerCase().includes(query);
      const matchesCategory =
        selected === "all" || card.dataset.category === selected;

      card.hidden = !(matchesText && matchesCategory);
      if (!card.hidden) visible++;
    });

    if (empty) empty.hidden = visible > 0;
  }

  search.addEventListener("input", filterItems);
  category.addEventListener("change", filterItems);
}

setupFilter("courseSearch", "courseCategory", "courseGrid", "emptyCourses");
setupFilter("productSearch", "productCategory", "productGrid", "emptyProducts");

// سبد خرید آزمایشی با localStorage
function getCart() {
  try {
    return JSON.parse(localStorage.getItem("novaCart")) || [];
  } catch {
    return [];
  }
}

function saveCart(cart) {
  localStorage.setItem("novaCart", JSON.stringify(cart));
}

document.querySelectorAll("[data-add-cart]").forEach(button => {
  button.addEventListener("click", () => {
    const cart = getCart();
    const id = button.dataset.id;
    const existing = cart.find(item => item.id === id);

    if (existing) {
      existing.quantity++;
    } else {
      cart.push({
        id,
        name: button.dataset.name,
        price: Number(button.dataset.price),
        quantity: 1
      });
    }

    saveCart(cart);
    button.textContent = "به سبد اضافه شد ✓";
  });
});

// نمایش سبد خرید
function renderCart() {
  const container = document.getElementById("cartItems");
  const totalElement = document.getElementById("cartTotal");
  if (!container || !totalElement) return;

  const cart = getCart();

  if (!cart.length) {
    container.innerHTML = '<p class="empty-state">سبد خریدت خالی است.</p>';
    totalElement.textContent = formatPrice(0);
    return;
  }

  container.innerHTML = cart.map(item => `
    <div class="cart-row">
      <div>
        <strong>${escapeHTML(item.name)}</strong>
        <p>${formatPrice(item.price)} × ${item.quantity}</p>
      </div>
      <button class="remove-btn" data-remove="${escapeHTML(item.id)}">
        حذف
      </button>
    </div>
  `).join("");

  const total = cart.reduce(
    (sum, item) => sum + item.price * item.quantity, 0
  );
  totalElement.textContent = formatPrice(total);

  container.querySelectorAll("[data-remove]").forEach(button => {
    button.addEventListener("click", () => {
      saveCart(getCart().filter(item => item.id !== button.dataset.remove));
      renderCart();
    });
  });
}

function escapeHTML(value) {
  return String(value).replace(/[&<>"']/g, char => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;"
  })[char]);
}

renderCart();

// فرم‌های نمایشی ورود و ثبت‌نام
document.querySelectorAll("[data-demo-form]").forEach(form => {
  form.addEventListener("submit", event => {
    event.preventDefault();

    const message = form.querySelector(".form-message");
    const password = form.querySelector("#password");
    const confirm = form.querySelector("#confirmPassword");

    if (confirm && password.value !== confirm.value) {
      message.textContent = "رمز عبور و تکرار آن یکسان نیستند.";
      return;
    }

    message.textContent =
      "فرم با موفقیت بررسی شد. برای فعال شدن حساب، باید بک‌اند متصل شود.";
  });
});

// دکمه آزمایشی پرداخت
const checkout = document.getElementById("checkoutBtn");
if (checkout) {
  checkout.addEventListener("click", () => {
    if (!getCart().length) {
      alert("سبد خرید خالی است.");
      return;
    }
    alert("این بخش هنوز به سیستم سفارش و پرداخت متصل نشده است.");
  });
}
