<?php
session_start();
require 'db.php';

// Load job list
// Check if applicant is logged in
$isApplicant = isset($_SESSION['applicant_email']);

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base SQL
$sql = "
    SELECT
        id,
        category,
        title,
        faculty,
        department,
        position,
        requirement_qualification,
        deadline,
        is_active,
        created_at
    FROM jobs
    WHERE (is_active = 1 OR is_active IS NULL)
      AND (deadline IS NULL OR deadline >= CURDATE())
";

$params = [];

// Search filter
if ($search !== '') {
    $sql .= " AND (
        category LIKE ?
        OR faculty LIKE ?
        OR department LIKE ?
        OR position LIKE ?
        OR requirement_qualification LIKE ?
    )";

    $like = "%$search%";
    $params = [$like, $like, $like, $like, $like];
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>EKSU Recruitment Portal</title>
<style>
body {
   font-family: 'Arial', sans-serif;
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
    background: url('https://eksu.edu.ng/wp-content/uploads/2020/09/EKSU-Acad-Buiding-2048x1536.jpg') no-repeat center center/cover;
    height: 300px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: black;
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
    color: black;
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
    color: #800000;
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
.deadline {
    font-size:13px;
    margin-bottom:4px;
}

.countdown {
    font-weight:bold;
    color:#004080;
    margin-bottom:8px;
}

.badge {
    padding:5px 8px;
    border-radius:5px;
    font-size:12px;
    color:white;
}

.badge.closed {
    background:#b00000;
}
td {
    vertical-align: top;
}

.action-cell {
    max-width: 220px;
    white-space: normal;
    word-wrap: break-word;
}

.closed-message {
    display: block;
    background: #b00000;
    color: #fff;
    padding: 8px 10px;
    border-radius: 5px;
    font-size: 13px;
    font-weight: bold;
    text-align: center;
    line-height: 1.4;
}

.apply-btn {
    display:inline-block;
    padding:6px 10px;
    background:#008000;
    color:white;
    border-radius:5px;
    text-decoration:none;
}
.apply-btn:hover {
    background:#006600;
}

footer {
    background: #800000;
    color: white;
    text-align: center;   
    padding: 20px;
    margin-top: 40px;
}
  /* Subtle link styling */
footer a {
color: #ffffff;
text-decoration: underline;
transition: opacity 0.3s ease, text-decoration-color 0.3s ease;
}


/* Hover effect */
footer a:hover {
opacity: 0.75;
text-decoration: none;
}


/* Reduce spacing on small screens */
@media (max-width: 600px) {
footer {
padding: 12px 10px;
margin-top: 25px;
font-size: 13px;
}


footer p {
margin: 8px 0;
}
}
</style>
</head>
<body>
<nav>
   <div class="nav-container">
      <img src="logo.jfif" alt="Site Logo">
      <div class="nav-text">
        <h1>Ekiti State University, Ado-Ekiti</h1>
        <h5>Recruitment Portal</h5>
      </div>
    </div>

    <!-- HAMBURGER MENU -->
    <div class="menu-area">
        <div class="hamburger" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>

        <div id="dropdownMenu" class="dropdown-menu">
            <?php if ($isApplicant): ?>
               <span>Welcome, <?= htmlspecialchars($_SESSION['applicant_email']); ?></span>
                <a href="applicant_dashboard.php">Applicant Dashboard</a>
                <a href="applicant_logout.php">Logout</a>
            <?php else: ?>
                <a href="applicant_login.php">Applicant Login</a>
                <a href="applicant_signup.php">Applicant Signup</a>
                <a href="admin_login.php">Admin Login</a>
                <a href="https://eksu.edu.ng/about-eksu/">About Us</a>
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
<?php if (count($jobs) > 0): ?>
<table>
    <h2>Current Vacancies</h2>
    <tr>
        <th>Job Category</th>
        <th>Faculty</th>
        <th>Department</th>
        <th>Position</th>
        <th>Qualification</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php foreach ($jobs as $index => $job): ?>
    <tr>
       <td><?= htmlspecialchars($job['category'] ?? 'N/A'); ?></td>
        <td><?= htmlspecialchars($job['faculty'] ?? 'N/A'); ?></td>
        <td><?= htmlspecialchars($job['department'] ?? 'N/A'); ?></td>
        <td><?= htmlspecialchars($job['position'] ?? 'N/A'); ?></td>
        <td><?= htmlspecialchars($job['qualification_display'] ?? $job['requirement_qualification'] ?? 'N/A'); ?></td>
  <td>
    <?php if (!empty($job['is_active'])): ?>
        <span style="color:green;font-weight:bold;">Active</span>
    <?php else: ?>
        <span style="color:red;font-weight:bold;">Inactive</span>
    <?php endif; ?>
</td>
        <td class="action-cell">
    <?php
        $isExpired = !empty($job['deadline']) && strtotime($job['deadline']) < time();
        $deadline  = $job['deadline'] ?? null;
    ?>

    <?php if ($isExpired): ?>
        <span class="closed-message">
            You cannot apply for this job.<br>
            It is closed.
        </span>
    <?php else: ?>

        <?php if ($isApplicant): ?>
            <a href="apply.php?job_id=<?= (int)$job['id']; ?>" class="apply-btn">
                Apply Now
            </a>
        <?php else: ?>
            <a href="applicant_login.php" class="apply-btn" style="background:#ff6600;">
                Login to Apply
            </a>
        <?php endif; ?>

        <?php if ($deadline): ?>
            <div class="deadline">
                Deadline:<br>
                <strong><?= date('d M Y, H:i', strtotime($deadline)) ?></strong>
            </div>

            <div class="countdown" data-deadline="<?= htmlspecialchars($deadline) ?>">
                Loading countdown...
            </div>
        <?php endif; ?>

    <?php endif; ?>
</td>


    </tr>
    <?php endforeach; ?>
</table>

<?php else: ?>
    <p class="notice">No job postings found.</p>
<?php endif; ?>


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
document.querySelectorAll('.countdown').forEach(timer => {
    const deadline = new Date(timer.dataset.deadline).getTime();

    function update() {
        const now = Date.now();
        const diff = deadline - now;

        if (diff <= 0) {
            timer.innerHTML = "<span class='badge closed'>Closed</span>";
            return;
        }

        const days  = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
        const mins  = Math.floor((diff / (1000 * 60)) % 60);

        timer.textContent = `Closes in ${days}d ${hours}h ${mins}m`;
        setTimeout(update, 60000);
    }

    update();
});
</script>

<footer>
    <p>
        <strong>Need help?</strong><br>
        For assistance or enquiries, please contact us on
        <a href="tel:+2348038558350" style="color:#fff; text-decoration:underline;">
            +234 803 855 8350
        </a>
        or visit our
        <a href="https://eksu.edu.ng/about-eksu/" style="color:#fff; text-decoration:underline;">
            About Us
        </a>
        page.
    </p>

    <p>
        &copy; 2026 EKSU Recruitment. All rights reserved.
    </p>
</footer>
</body>
</html>
