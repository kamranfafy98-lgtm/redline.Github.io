// NOVA - رفتارهای تعاملی صفحه اصلی
const menuToggle = document.getElementById("menuToggle");
const mainNav = document.getElementById("mainNav");
const searchToggle = document.getElementById("searchToggle");
const searchPanel = document.getElementById("searchPanel");
const toast = document.getElementById("toast");

function showToast(message) {
  if (!toast) return;
  toast.textContent = message;
  toast.classList.add("show");
  window.clearTimeout(showToast.timer);
  showToast.timer = window.setTimeout(() => toast.classList.remove("show"), 2600);
}

if (menuToggle && mainNav) {
  menuToggle.addEventListener("click", () => {
    const isOpen = mainNav.classList.toggle("open");
    menuToggle.setAttribute("aria-expanded", String(isOpen));
    menuToggle.textContent = isOpen ? "×" : "☰";
  });

  mainNav.querySelectorAll("a").forEach(link => {
    link.addEventListener("click", () => {
      mainNav.classList.remove("open");
      menuToggle.setAttribute("aria-expanded", "false");
      menuToggle.textContent = "☰";
    });
  });
}

if (searchToggle && searchPanel) {
  searchToggle.addEventListener("click", () => {
    searchPanel.classList.toggle("open");
    if (searchPanel.classList.contains("open")) {
      document.getElementById("siteSearch")?.focus();
    }
  });
}

document.getElementById("searchSubmit")?.addEventListener("click", () => {
  const query = document.getElementById("siteSearch").value.trim();
  if (!query) {
    showToast("لطفاً عبارت موردنظرت رو وارد کن.");
    return;
  }
  showToast(`جستجوی «${query}» در نسخه نمایشی فعال می‌شود.`);
});

document.getElementById("siteSearch")?.addEventListener("keydown", event => {
  if (event.key === "Enter") document.getElementById("searchSubmit").click();
});

// فیلتر نمایشی محصولات فروشگاه
const tabs = document.querySelectorAll(".shop-tab");
const products = document.querySelectorAll(".product-card");
const resultCount = document.getElementById("resultCount");

tabs.forEach(tab => {
  tab.addEventListener("click", () => {
    tabs.forEach(item => {
      item.classList.remove("active");
      item.setAttribute("aria-selected", "false");
    });
    tab.classList.add("active");
    tab.setAttribute("aria-selected", "true");

    let visible = 0;
    products.forEach(product => {
      const shouldShow = tab.dataset.filter === "all" || product.dataset.type === tab.dataset.filter;
      product.hidden = !shouldShow;
      if (shouldShow) visible++;
    });
    if (resultCount) resultCount.textContent = `${visible} محصول`;
  });
});

// سبد خرید نمایشی؛ در مرحله PHP به سبد خرید واقعی متصل می‌شود.
let cartCount = 0;
document.querySelectorAll(".quick-add").forEach(button => {
  button.addEventListener("click", () => {
    cartCount++;
    const counter = document.getElementById("cartCount");
    if (counter) counter.textContent = new Intl.NumberFormat("fa-IR").format(cartCount);
    showToast(`«${button.dataset.name}» به سبد خرید نمایشی اضافه شد.`);
  });
});

// فرم خبرنامه در این نسخه فقط اعتبارسنجی سمت مرورگر دارد.
document.getElementById("newsletterForm")?.addEventListener("submit", event => {
  event.preventDefault();
  const email = document.getElementById("newsletterEmail");
  if (!email.checkValidity()) {
    email.reportValidity();
    return;
  }
  showToast("ممنون! فرم در نسخه نمایشی ثبت شد.");
  email.value = "";
});
