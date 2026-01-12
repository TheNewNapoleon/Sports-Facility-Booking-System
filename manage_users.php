<?php
require_once "Database.php";  // your DB connection class

$db = new Database();
$conn = $db->getConnection();

$sql = "SELECT * FROM users ORDER BY user_id ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users - TARUMT Sports Booking System</title>

<style>
    body { font-family: Arial; background:#f5f5f5; margin:0; }
    header { background:#ff7a00; padding:20px; color:white; text-align:center; font-size:24px; }
    .container { width:95%; margin:20px auto; background:white; padding:20px; border-radius:12px; box-shadow:0 3px 8px rgba(0,0,0,0.2); overflow-x:auto; }
    table { width:100%; border-collapse:collapse; min-width:1000px; }
    th { background:#ff9a32; color:white; padding:12px; }
    td { padding:12px; border-bottom:1px solid #ddd; }
    .action-btn { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; color:white; }
    .read { background:#28a745; }
    .edit { background:#007bff; }
    .delete { background:#d9534f; }
</style>

<script>
function confirmDelete(userid){
    if (confirm("Are you sure you want to delete user: " + userid + "?")){
        window.location.href = "delete_user.php?id=" + userid;
    }
}
</script>

</head>
<body>

<header>Manage Users</header>

<div class="container">

<table>
    <tr>
        <th>User ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Living Place</th>
        <th>Phone</th>
        <th>DOB</th>
        <th>Gender</th>
        <th>Action</th>
    </tr>

<?php
if ($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        echo "
        <tr>
            <td>{$row['user_id']}</td>
            <td>{$row['name']}</td>
            <td>{$row['email']}</td>
            <td>{$row['role']}</td>
            <td>{$row['living_place']}</td>
            <td>{$row['phone_number']}</td>
            <td>{$row['date_of_birth']}</td>
            <td>{$row['gender']}</td>
            <td>
                <button class='action-btn read' onclick=\"window.location.href='read_user.php?id={$row['user_id']}'\">Read</button>
                <button class='action-btn edit' onclick=\"window.location.href='edit_user.php?id={$row['user_id']}'\">Edit</button>
                <button class='action-btn delete' onclick=\"confirmDelete('{$row['user_id']}')\">Delete</button>
            </td>
        </tr>";
    }
}
?>
</table>

</div>
</body>
</html>
