<?php
require_once __DIR__ . '/../api/db-config.php';

$pdo = getDBConnection();
$now = date('Y-m-d H:i:s');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

$count = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE status = 'published' AND (publish_date IS NULL OR publish_date <= ?)");
$count->execute([$now]);
$total = (int)$count->fetchColumn();
$pages = (int)ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT b.id, b.title, b.slug, b.excerpt, b.feature_image, b.feature_image_alt, b.publish_date, b.read_time, a.name AS author_name
    FROM blogs b LEFT JOIN blog_authors a ON b.author_id = a.id
    WHERE b.status = 'published' AND (b.publish_date IS NULL OR b.publish_date <= ?)
    ORDER BY COALESCE(b.publish_date, b.created_at) DESC LIMIT ? OFFSET ?");
$stmt->execute([$now, $perPage, $offset]);
$blogs = $stmt->fetchAll();
$siteUrl = 'https://www.onlineuttaranchaluniversity.com';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Blog | Online Uttaranchal University</title>
<meta name="description" content="Insights, updates and career guidance from Online Uttaranchal University.">
<link rel="canonical" href="<?= $siteUrl ?>/blog/">
<link rel="stylesheet" href="/assets/css/blog-content.css">
</head>
<body>
<header class="uu-blog-header">
  <div class="uu-blog-nav">
    <a href="/index.html" class="uu-blog-brand"><img src="/assets/images/24_onlineUU.png" alt="Online Uttaranchal University"> <span>Online Uttaranchal University</span></a>
    <nav aria-label="Blog navigation"><a href="/index.html">Home</a><a href="/blog/" aria-current="page">Blog</a><a class="uu-blog-apply" href="/apply.html">Apply Now</a></nav>
  </div>
</header>
<main>
<section class="uu-blog-hero"><div class="uu-blog-container"><span class="uu-blog-kicker">Online learning, made clearer</span><h1>Insights &amp; Updates</h1><p>Practical guidance for your degree, career and next step.</p></div></section>
<section class="uu-blog-section"><div class="uu-blog-container"><div class="uu-blog-grid">
<?php foreach ($blogs as $blog): ?>
<article class="uu-blog-card">
  <?php if ($blog['feature_image']): ?><img src="<?= htmlspecialchars($blog['feature_image']) ?>" alt="<?= htmlspecialchars($blog['feature_image_alt'] ?: $blog['title']) ?>" loading="lazy"><?php else: ?><div class="uu-blog-placeholder">UU</div><?php endif; ?>
  <div class="uu-blog-card-body"><div class="uu-blog-meta"><?= $blog['publish_date'] ? date('d M Y', strtotime($blog['publish_date'])) : 'Uttaranchal University' ?><?php if ($blog['read_time']): ?> · <?= (int)$blog['read_time'] ?> min read<?php endif; ?></div><h2><a href="/blog/<?= urlencode($blog['slug']) ?>"><?= htmlspecialchars($blog['title']) ?></a></h2><p><?= htmlspecialchars($blog['excerpt']) ?></p><a class="uu-blog-read" href="/blog/<?= urlencode($blog['slug']) ?>">Read article <span aria-hidden="true">&rarr;</span></a></div>
</article>
<?php endforeach; ?>
</div>
<?php if (!$blogs): ?><div class="uu-blog-empty"><h2>Articles are coming soon</h2><p>Check back for updates from Online Uttaranchal University.</p></div><?php endif; ?>
<?php if ($pages > 1): ?><nav class="uu-blog-pagination" aria-label="Blog pages"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="<?= $i === $page ? 'active' : '' ?>" href="?page=<?= $i ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
</div></section>
</main>
<footer class="uu-site-footer">
  <div class="uu-blog-container">
    <div class="uu-sf-grid">
      <div class="uu-sf-col">
        <h4>About</h4>
        <p>The university came into establishment in 2013, vide Uttaranchal University Act, 2012 (Uttarakhand Act. No. 11 of 2013) as a Private University. It's located in Dehradun, the capital city of Uttarakhand. Recognized by UGC under Sections 2(f) and 12(B) of the UGC Act, 1956. <a href="/about.html">Read More</a></p>
      </div>
      <div class="uu-sf-col">
        <h4>Programs</h4>
        <ul>
          <li><a href="/mba.html">Master of Business Administration (MBA)</a></li>
          <li><a href="/mca.html">Master of Computer Applications (MCA)</a></li>
          <li><a href="/bba.html">Bachelor of Business Administration (BBA)</a></li>
          <li><a href="/bca.html">Bachelor of Computer Applications (BCA)</a></li>
          <li><a href="/ba.html">Bachelor of Arts (BA)</a></li>
          <li><a href="/executive-mba.html">Executive MBA</a></li>
        </ul>
      </div>
      <div class="uu-sf-col">
        <h4>Others</h4>
        <ul>
          <li><a href="/fee-refund-and-cancellation-policy.html">Fee Refund &amp; Cancellation Policy</a></li>
          <li><a href="/faculty.html">Faculty</a></li>
          <li><a href="/ciqa.html">CIQA</a></li>
          <li><a href="/faqs.html">FAQs</a></li>
          <li><a href="/how-to-apply.html">How to Apply?</a></li>
          <li><a href="https://www.uudoon.in/" target="_blank" rel="noopener">Uttaranchal University</a></li>
          <li><a href="/disclosure.html">Disclosure</a></li>
          <li><a href="/grievance-redressal-cell.html">Grievance Redressal</a></li>
        </ul>
      </div>
      <div class="uu-sf-col">
        <h4>Contact</h4>
        <p><span class="uu-sf-muted">Address:</span><br>Premnagar, Dehradun - 248007,<br>Uttarakhand, INDIA</p>
        <ul>
          <li><span class="uu-sf-muted">Admission Helpline:</span><br><a href="tel:9266530366">9266530366</a></li>
          <li><span class="uu-sf-muted">Email:</span><br><a href="mailto:admissions@onlineuttaranchaluniversity.com">admissions@onlineuttaranchaluniversity.com</a></li>
          <li><span class="uu-sf-muted">Academic Helpline:</span><br><a href="tel:08071176059">08071176059</a> / <a href="mailto:helpdesk@onlineuttaranchaluniversity.com">helpdesk@onlineuttaranchaluniversity.com</a></li>
        </ul>
      </div>
    </div>
    <div class="uu-sf-bottom">
      <img src="/assets/images/uu-sm-logo-gray.svg" alt="Uttaranchal University" loading="lazy">
      <p>&copy; <?= date('Y') ?> Uttaranchal University. All rights reserved.</p>
    </div>
  </div>
</footer>

<div class="fixed-side-btns">
  <a href="https://wa.me/919266530366" target="_blank" rel="noopener" class="fsb-whatsapp" aria-label="Chat on WhatsApp">
    <span class="fsb-icon">&#x1F4AC;</span>
    <span class="fsb-label"><small>WhatsApp</small><strong>9266530366</strong></span>
  </a>
  <a href="tel:9266530366" class="fsb-phone" aria-label="Call Helpline">
    <span class="fsb-icon">&#x1F4DE;</span>
    <span class="fsb-label"><small>Helpline</small><strong>9266530366</strong></span>
  </a>
</div>

<style>
.uu-site-footer{background:#1a1a2e;color:#ccc;padding:50px 0 20px;}
.uu-sf-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:30px;}
.uu-sf-col h4{color:#fff;font-size:18px;font-weight:700;line-height:1.2;margin:0 0 16px;padding:0 0 10px;border:0;position:relative;display:block;}
.uu-sf-col h4:after{content:'';position:absolute;bottom:0;left:0;width:40px;height:3px;background:#c9001a;border-radius:2px;}
.uu-sf-col p{font-size:13px;line-height:1.7;margin:0 0 8px;}
.uu-sf-col ul{list-style:none;margin:0;padding:0;}
.uu-sf-col ul li{margin-bottom:8px;}
.uu-sf-col ul li a,.uu-sf-col p a{color:#aaa;font-size:13px;text-decoration:none;transition:color .2s;}
.uu-sf-col ul li a:hover,.uu-sf-col p a:hover{color:#fff;}
.uu-sf-muted{color:#888;font-size:12px;}
.uu-sf-bottom{text-align:center;padding-top:30px;margin-top:30px;border-top:1px solid rgba(255,255,255,.1);}
.uu-sf-bottom img{height:30px;margin:0 auto 10px;opacity:.5;display:block;}
.uu-sf-bottom p{font-size:13px;color:#888;margin:0;}
.fixed-side-btns{position:fixed;right:0;top:50%;transform:translateY(-50%);z-index:900;display:flex;flex-direction:column;gap:2px;}
.fixed-side-btns a{display:flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:8px 0 0 8px;color:#fff;text-decoration:none;overflow:hidden;transition:width .3s;box-shadow:none;}
.fsb-icon{width:48px;height:48px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.fsb-label{display:none;}
.fsb-label small{display:block;font-size:11px;opacity:.85;line-height:1.2;}
.fsb-label strong{display:block;font-size:13px;line-height:1.3;}
.fixed-side-btns a:hover .fsb-label{display:none;}
.fsb-whatsapp{background:#25d366;}.fsb-whatsapp:hover{background:#1ebe5d;}
.fsb-phone{background:#c9001a;}.fsb-phone:hover{background:#a80015;}
.fsb-whatsapp .fsb-icon{background:#25d366;}.fsb-phone .fsb-icon{background:#c9001a;}
@media(max-width:900px){.uu-sf-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.fixed-side-btns{display:none;}}
@media(max-width:620px){.uu-sf-grid{grid-template-columns:1fr;}}
</style>
</body>
</html>
