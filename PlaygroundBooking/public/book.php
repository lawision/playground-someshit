<?php
require_once __DIR__ . "/../config/db.php";
include __DIR__ . "/../includes/header.php";

// require login
if (empty($_SESSION['user'])) {
    // redirect to login and come back after
    $_SESSION['after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// if form submitted (POST) -> create booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $playground_id = isset($_POST['playground_id']) ? (int)$_POST['playground_id'] : 0;
    $time_slot_id  = isset($_POST['time_slot_id']) ? (int)$_POST['time_slot_id'] : 0;
    $slot_date     = isset($_POST['slot_date']) ? $_POST['slot_date'] : '';
    $kids_count    = isset($_POST['kids_count']) ? (int)$_POST['kids_count'] : 0;

    // basic validation
    $errors = [];
    if ($playground_id <= 0 || $time_slot_id <= 0) $errors[] = "Invalid playground or time slot.";
    if (!$slot_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $slot_date)) $errors[] = "Invalid date.";
    if ($kids_count <= 0) $errors[] = "Kids count must be at least 1.";

    if (empty($errors)) {
        try {
            // begin transaction to avoid race
            $conn->beginTransaction();

            // check playground capacity
            $s = $conn->prepare("SELECT capacity, name FROM playgrounds WHERE id = ? FOR UPDATE");
            $s->execute([$playground_id]);
            $pg = $s->fetch(PDO::FETCH_ASSOC);
            if (!$pg) throw new Exception("Playground not found.");

            // compute already booked kids for this slot and date
            $q = "SELECT COALESCE(SUM(kids_count),0) AS total FROM bookings
                  WHERE playground_id = ? AND slot_date = ? AND time_slot_id = ? AND status IN ('pending','confirmed') FOR UPDATE";
            $st = $conn->prepare($q);
            $st->execute([$playground_id, $slot_date, $time_slot_id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $already = (int)$row['total'];

            if ($already + $kids_count > (int)$pg['capacity']) {
                // rollback
                $conn->rollBack();
                $errors[] = "Not enough remaining capacity. Remaining: " . max(0, (int)$pg['capacity'] - $already);
            } else {
                // insert booking
                $ins = $conn->prepare("INSERT INTO bookings (user_id, playground_id, slot_date, time_slot_id, kids_count, total_price, status, created_at)
                                       VALUES (?, ?, ?, ?, ?, 0, 'confirmed', NOW())");
                $ins->execute([$user['id'], $playground_id, $slot_date, $time_slot_id, $kids_count]);
                $booking_id = $conn->lastInsertId();

                // commit
                $conn->commit();

                // success message and simple confirmation
                echo "<h2>Booking Confirmed</h2>";
                echo "<p>Your booking for <strong>" . htmlspecialchars($pg['name']) . "</strong> on <strong>" . htmlspecialchars($slot_date) . "</strong> has been confirmed.</p>";
                echo "<p>Booking ID: <strong>" . htmlspecialchars($booking_id) . "</strong></p>";
                echo '<p><a class="btn" href="my_bookings.php">View My Bookings</a> <a class="btn" href="index.php">Back to Playgrounds</a></p>';
                echo "</main></body></html>";
                exit;
            }
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $errors[] = "Booking failed: " . $e->getMessage();
        }
    }

    // show form again with errors
}

// If GET (or error), show pre-fill booking form
$playground_id = isset($_GET['playground_id']) ? (int)$_GET['playground_id'] : (isset($_POST['playground_id']) ? (int)$_POST['playground_id'] : 0);
$time_slot_id  = isset($_GET['time_slot_id']) ? (int)$_GET['time_slot_id'] : (isset($_POST['time_slot_id']) ? (int)$_POST['time_slot_id'] : 0);
$slot_date     = isset($_GET['slot_date']) ? $_GET['slot_date'] : (isset($_POST['slot_date']) ? $_POST['slot_date'] : '');
$kids_count    = isset($_GET['kids_count']) ? (int)$_GET['kids_count'] : (isset($_POST['kids_count']) ? (int)$_POST['kids_count'] : 1);

// load playground and time slot info for display
$pg = null;
if ($playground_id > 0) {
    $s = $conn->prepare("SELECT * FROM playgrounds WHERE id = ?");
    $s->execute([$playground_id]);
    $pg = $s->fetch(PDO::FETCH_ASSOC);
}
$ts = null;
if ($time_slot_id > 0) {
    $s2 = $conn->prepare("SELECT * FROM time_slots WHERE id = ?");
    $s2->execute([$time_slot_id]);
    $ts = $s2->fetch(PDO::FETCH_ASSOC);
}

// show errors if any
if (!empty($errors)) {
    echo "<div style='color:red'><ul>";
    foreach ($errors as $err) echo "<li>" . htmlspecialchars($err) . "</li>";
    echo "</ul></div>";
}
?>

<h1>Confirm Booking</h1>

<?php if (!$pg || !$ts || !$slot_date): ?>
  <p>Missing booking information. Please start from the playground page.</p>
  <p><a class="btn" href="index.php">Back to Playgrounds</a></p>
<?php else: ?>

  <div class="card">
    <h3><?=htmlspecialchars($pg['name'])?></h3>
    <p class="muted"><?=htmlspecialchars($pg['location'])?> — Capacity: <?=htmlspecialchars($pg['capacity'])?></p>
    <p><strong>Date:</strong> <?=htmlspecialchars($slot_date)?> &nbsp; <strong>Time:</strong> <?=substr($ts['start_time'],0,5)?> - <?=substr($ts['end_time'],0,5)?></p>

    <form method="POST" action="book.php">
      <input type="hidden" name="playground_id" value="<?=htmlspecialchars($playground_id)?>">
      <input type="hidden" name="time_slot_id" value="<?=htmlspecialchars($time_slot_id)?>">
      <input type="hidden" name="slot_date" value="<?=htmlspecialchars($slot_date)?>">

      <label>Number of kids (max <?=htmlspecialchars($pg['capacity'])?>)</label><br>
      <input type="number" name="kids_count" min="1" max="<?=htmlspecialchars($pg['capacity'])?>" value="<?=htmlspecialchars($kids_count)?>" required><br><br>

      <button class="btn" type="submit">Confirm & Book</button>
      <a class="btn" href="playground.php?id=<?=urlencode($pg['id'])?>" style="background:#666">Back</a>
    </form>
  </div>

<?php endif; ?>

</main>
</body>
</html>
