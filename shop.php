
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>فروشگاه | NOVA</title>
  <link rel="stylesheet" href="cstyle.css">
  <script src="js/app.js" defer></script>
</head>
<body>
  <header class="header">
    <a class="logo" href="index.php">NOVA</a>
    <nav><a href="index.php">خانه</a><a href="courses.php">دوره‌ها</a><a class="active" href="shop.php">فروشگاه</a></nav>
    <div class="header-actions"><a href="cart.php">سبد خرید 🛒</a><a href="login.php">ورود</a></div>
  </header>

  <main class="container">
    <section class="page-heading">
      <span class="eyebrow">فروشگاه NOVA</span>
      <h1>ابزارهای مسیر یادگیری تو</h1>
      <p>محصولات آموزشی و دیجیتال را اینجا پیدا کن.</p>
    </section>

    <section class="filters">
      <input id="productSearch" type="search" placeholder="جست‌وجوی محصول...">
      <select id="productCategory">
        <option value="all">همه محصولات</option>
        <option value="digital">دیجیتال</option>
        <option value="stationery">لوازم‌التحریر</option>
        <option value="book">کتاب</option>
      </select>
    </section>

    <section class="card-grid" id="productGrid">
      <article class="item-card" data-category="book">
        <div class="item-cover">📘</div>
        <div class="item-content">
          <span class="tag">کتاب</span>
          <h3>کتاب آموزش برنامه‌نویسی</h3>
          <p>مناسب شروع مسیر برنامه‌نویسی.</p>
          <div class="item-footer"><strong>۲۸۰٬۰۰۰ تومان</strong><a href="product-details.html">جزئیات</a></div>
          <button class="btn full" data-add-cart data-id="book-01" data-name="کتاب آموزش برنامه‌نویسی" data-price="280000">افزودن به سبد</button>
        </div>
      </article>

      <article class="item-card" data-category="stationery">
        <div class="item-cover">📓</div>
        <div class="item-content">
          <span class="tag">لوازم‌التحریر</span>
          <h3>دفتر یادداشت</h3>
          <p>برای ثبت نکته‌ها و برنامه‌ها.</p>
          <div class="item-footer"><strong>۱۲۰٬۰۰۰ تومان</strong><a href="product-details.html">جزئیات</a></div>
          <button class="btn full" data-add-cart data-id="notebook-01" data-name="دفتر یادداشت" data-price="120000">افزودن به سبد</button>
        </div>
      </article>

      <article class="item-card" data-category="digital">
        <div class="item-cover">🗂️</div>
        <div class="item-content">
          <span class="tag">دیجیتال</span>
          <h3>قالب برنامه‌ریزی درسی</h3>
          <p>فایل دیجیتال برای نظم دادن به مطالعه.</p>
          <div class="item-footer"><strong>۹۰٬۰۰۰ تومان</strong><a href="product-details.html">جزئیات</a></div>
          <button class="btn full" data-add-cart data-id="planner-01" data-name="قالب برنامه‌ریزی درسی" data-price="90000">افزودن به سبد</button>
        </div>
      </article>
    </section>
    <p id="emptyProducts" class="empty-state" hidden>محصولی پیدا نشد.</p>
  </main>

  <footer class="footer">© NOVA</footer>
</body>
</html>
