<?php
require_once __DIR__ . "/../config/connect.php";
include __DIR__ . "/../includes/header.php";

// fetch playgrounds
$stmt = $conn->prepare("SELECT * FROM playgrounds WHERE active = 1 ORDER BY id");
$stmt->execute();
$playgrounds = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Available Playgrounds</h1>

<?php if (!$playgrounds): ?>
  <p>No playgrounds set up yet. (Admin: create playgrounds in the admin area.)</p>
<?php endif; ?>

<?php foreach ($playgrounds as $pg): ?>
  <div class="card">
    <h3><?=htmlspecialchars($pg['name'])?></h3>
    <p class="muted"><?=htmlspecialchars($pg['location'] ?? '')?> — Capacity: <?=htmlspecialchars($pg['capacity'])?></p>
    <p><?=nl2br(htmlspecialchars($pg['description'] ?? ''))?></p>
    <a class="btn" href="playground.php?id=<?=urlencode($pg['id'])?>">View & Book</a>
  </div>
<?php endforeach; ?>

</main>
</body>
</html>
