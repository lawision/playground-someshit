<?php
require_once __DIR__ . "/../config/db.php";
include __DIR__ . "/../includes/header.php";

$pg_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pg_id <= 0) {
    echo "<p>Invalid playground.</p></main></body></html>";
    exit;
}

// get playground
$stmt = $conn->prepare("SELECT * FROM playgrounds WHERE id = ?");
$stmt->execute([$pg_id]);
$playground = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$playground) {
    echo "<p>Playground not found.</p></main></body></html>";
    exit;
}

// fetch time slots (ordered)
$stmt = $conn->prepare("SELECT * FROM time_slots ORDER BY start_time");
$stmt->execute();
$time_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// utility: check availability counts for a specific date+slot
function available_for_slot($conn, $playground_id, $slot_date, $time_slot_id) {
    // count kids already booked (pending or confirmed)
    $sql = "SELECT COALESCE(SUM(kids_count),0) AS total FROM bookings
            WHERE playground_id = ? AND slot_date = ? AND time_slot_id = ? AND status IN ('pending','confirmed')";
    $s = $conn->prepare($sql);
    $s->execute([$playground_id, $slot_date, $time_slot_id]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    return (int)$row['total'];
}

// show next 7 days
$days_to_show = 7;
$today = new DateTime('today');

?>
<h1><?=htmlspecialchars($playground['name'])?></h1>
<p class="muted"><?=htmlspecialchars($playground['location'])?> — Capacity: <?=htmlspecialchars($playground['capacity'])?></p>
<p><?=nl2br(htmlspecialchars($playground['description']))?></p>

<?php if (empty($time_slots)): ?>
  <p>No time slots configured. Ask admin to add time slots.</p>
<?php else: ?>

  <h2>Available Dates & Time Slots (next <?= $days_to_show ?> days)</h2>

  <?php for ($d = 0; $d < $days_to_show; $d++):
      $date = clone $today;
      $date->modify("+$d day");
      $slot_date = $date->format('Y-m-d');
  ?>
    <div class="card">
      <h3><?= $date->format('l, F j, Y') ?></h3>

      <table>
        <thead>
          <tr><th>Time</th><th>Booked</th><th>Remaining</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php foreach ($time_slots as $ts):
            $booked = available_for_slot($conn, $playground['id'], $slot_date, $ts['id']);
            $remaining = max(0, (int)$playground['capacity'] - $booked);
          ?>
            <tr>
              <td><?=substr($ts['start_time'],0,5)?> - <?=substr($ts['end_time'],0,5)?></td>
              <td><?= $booked ?></td>
              <td><?= $remaining ?></td>
              <td>
                <?php if ($remaining <= 0): ?>
                  <span class="muted">Full</span>
                <?php else: ?>
                  <form method="GET" action="book.php" style="display:inline-block">
                    <input type="hidden" name="playground_id" value="<?=htmlspecialchars($playground['id'])?>">
                    <input type="hidden" name="slot_date" value="<?=htmlspecialchars($slot_date)?>">
                    <input type="hidden" name="time_slot_id" value="<?=htmlspecialchars($ts['id'])?>">
                    <label style="margin-right:8px">
                      <input type="number" name="kids_count" min="1" max="<?=htmlspecialchars($remaining)?>" value="1" style="width:60px">
                    </label>
                    <button class="btn" type="submit">Book</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endfor; ?>

<?php endif; ?>

</main>
</body>
</html>
