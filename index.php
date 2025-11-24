<?php
session_start();

// Load job list
$jobs_file = 'jobs.json';
$jobs = file_exists($jobs_file) ? json_decode(file_get_contents($jobs_file), true) : [];

// Check if applicant is logged in
$isApplicant = isset($_SESSION['applicant_email']);

// Handle search query
$search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
if (!empty($search)) {
    $jobs = array_filter($jobs, function ($job) use ($search) {
        return str_contains(strtolower($job['title']), $search)
            || str_contains(strtolower($job['faculty']), $search)
            || str_contains(strtolower($job['department']), $search)
            || str_contains(strtolower($job['position']), $search)
            || str_contains(strtolower($job['qualification']), $search);
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>EKSU Recruitment Portal</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f0f4f8;
    margin: 0;
}
nav {
    background: #800000;
    color: #fff;
    padding: 15px 10%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.nav-container {
    display: flex;
    align-items: center;
    gap: 15px;
}
.nav-container img {
    width: 70px;
    height: 70px;
    border-radius: 5px;
}
nav h1 {
    font-size: 22px;
    margin: 0;
}
.nav-text h5 {
    margin: 0;
    font-size: 14px;
    font-weight: normal;
    color: #ddd;
}
/* HAMBURGER MENU */
.menu-area {
    position: right;
}

.hamburger {
    width: 35px;
    height: 26px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    cursor: pointer;
}

.hamburger div {
    height: 5px;
    background:#C9A959; /* GOLD */
    border-radius: 4px;
    transition: 0.3s;
}

.hamburger:hover div {
    background: #ffffff;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 45px;
    right: 0;
    width: 220px;
    background: #ffffff;
    border: 2px solid #800000;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.25);
    overflow: hidden;
    animation: fadeIn 0.25s ease-out;
    z-index: 1000;
}

.dropdown-menu a {
    display: block;
    padding: 14px;
    text-decoration: none;
    color: #333;
    font-weight: 500;
    border-bottom: 1px solid #f1f1f1;
}

.dropdown-menu a:hover {
    background: #800000;
    color: #fff;
}

.dropdown-menu a:last-child {
    border-bottom: none;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* === HERO SECTION === */
.hero {
    position: relative;
    background: url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f') no-repeat center center/cover;
    height: 200px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: White;
    text-align: center;
}
.hero::before {
    content: "";
    position: absolute;
    top: 0; left: 0;
    right: 0; bottom: 0;
}
.hero-content {
    position: relative;
    z-index: 1;
    max-width: 700px;
}
.hero-content h2 {
    font-size: 30px;
    margin-bottom: 10px;
    font-weight: bold;
}
.hero-content p {
    font-size: 16px;
    margin-bottom: 20px;
}
.search-bar {
    display: flex;
    justify-content: center;
    margin-top: 10px;
}
.search-bar input[type="text"] {
    width: 70%;
    padding: 14px;
    border: none;
    border-radius: 5px 0 0 5px;
    font-size: 16px;
    outline: none;
}
.search-bar button {
    padding: 14px 20px;
    background: #800000;
    color: white;
    border: none;
    border-radius: 0 5px 5px 0;
    cursor: pointer;
    font-weight: bold;
}
.search-bar button:hover {
    background: #800000;
}

/* === MAIN CONTENT === */
.container {
    max-width: 1000px;
    margin: 40px auto;
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
h2 {
    color: #f9f8f8ff;
    text-align: center;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th, td {
    border: 1px solid #ccc;
    padding: 12px;
    text-align: left;
}
th {
    background: #800000;
    color: white;
}
.apply-btn {
    display: inline-block;
    background: #800000;
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
}
.apply-btn:hover {
    background: #660000;
}
.notice {
    text-align: center;
    margin-top: 15px;
}
footer {
    background: #800000;
    color: white;
    text-align: center;
    padding: 20px;
    margin-top: 40px;
}
</style>
</head>
<body>
<nav>
   <div class="nav-container">
      <img src="logo.jfif" alt="Site Logo">
      <div class="nav-text">
        <h1>Ekiti State University, Ado-Ekiti</h1>
        <h5>Job Recruitment Portal</h5>
      </div>
    </div>

    <!-- HAMBURGER MENU -->
    <div class="menu-area">
        <div class="hamburger" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
        </div>

        <div id="dropdownMenu" class="dropdown-menu">
            <?php if ($isApplicant): ?>
               <span>Welcome, <?= htmlspecialchars($_SESSION['applicant_email']); ?></span>
                <a href="applicant_dashboard.php">Applicant Dashboard</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="applicant_login.php">Applicant Login</a>
                <a href="applicant_signup.php">Applicant Signup</a>
                <a href="admin_login.php">Admin Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- HERO SECTION -->
<section class="hero">
  <div class="hero-content">
    <h2>Find Your Ideal Career at EKSU</h2>
    <p>Search and apply for academic and non-academic positions that match your qualifications.</p>
    <form method="GET" class="search-bar">
      <input type="text" name="search" placeholder="Search by title, faculty, or department..." value="<?= htmlspecialchars($search); ?>">
      <button type="submit">🔍 Search</button>
    </form>
  </div>
</section>

<!-- JOB LISTINGS -->
<div class="container">
  <h2>Available Job Listings</h2>
  <?php if (count($jobs) > 0): ?>
    <table>
      <tr>
        <th>Title</th>
        <th>Faculty</th>
        <th>Department</th>
        <th>Position</th>
        <th>Qualification</th>
        <th>Action</th>
      </tr>
      <?php foreach ($jobs as $index => $job): ?>
        <tr>
          <td><?= htmlspecialchars($job['title'] ?? 'N/A'); ?></td>
          <td><?= htmlspecialchars($job['faculty'] ?? 'N/A'); ?></td>
          <td><?= htmlspecialchars($job['department'] ?? 'N/A'); ?></td>
          <td><?= htmlspecialchars($job['position'] ?? 'N/A'); ?></td>
          <td><?= htmlspecialchars($job['qualification'] ?? 'N/A'); ?></td>
          <td>
            <?php if ($isApplicant): ?>
              <a href="apply.php?job_id=<?= urlencode($index); ?>" class="apply-btn">Apply Now</a>
            <?php else: ?>
              <a href="applicant_login.php" class="apply-btn" style="background:#ff6600;">Login to Apply</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="notice">No job postings found for your search. Please try again.</p>
  <?php endif; ?>
</div>
<script>
function toggleMenu() {
    const menu = document.getElementById("dropdownMenu");
    menu.style.display = (menu.style.display === "block") ? "none" : "block";
}

document.addEventListener("click", function(event) {
    const menu = document.getElementById("dropdownMenu");
    const hamburger = document.querySelector(".hamburger");

    if (!hamburger.contains(event.target) && !menu.contains(event.target)) {
        menu.style.display = "none";
    }
});
</script>

<footer>
    <p>&copy; 2025 EKSU Recruitment. All rights reserved.</p>
</footer>
</body>
</html>
