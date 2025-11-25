<?php
require_once __DIR__ . "/../config/db.php";
include __DIR__ . "/../includes/header.php";

if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
$user = $_SESSION['user'];

$stmt = $conn->prepare("SELECT b.*, p.name AS playground_name, ts.start_time, ts.end_time
                        FROM bookings b
                        JOIN playgrounds p ON p.id = b.playground_id
                        JOIN time_slots ts ON ts.id = b.time_slot_id
                        WHERE b.user_id = ? ORDER BY b.slot_date DESC, ts.start_time");
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>My Bookings</h1>

<?php if (!$bookings): ?>
  <p>You have no bookings yet. <a href="index.php">Book now</a>.</p>
<?php else: ?>

<table>
  <thead><tr><th>Booking ID</th><th>Playground</th><th>Date</th><th>Time</th><th>Kids</th><th>Status</th></tr></thead>
  <tbody>
    <?php foreach ($bookings as $b): ?>
      <tr>
        <td><?=htmlspecialchars($b['id'])?></td>
        <td><?=htmlspecialchars($b['playground_name'])?></td>
        <td><?=htmlspecialchars($b['slot_date'])?></td>
        <td><?=substr($b['start_time'],0,5)?> - <?=substr($b['end_time'],0,5)?></td>
        <td><?=htmlspecialchars($b['kids_count'])?></td>
        <td><?=htmlspecialchars($b['status'])?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php endif; ?>

</main>
</body>
</html>
