<?php
session_start();

// === Připojení k databázi ===
$host = "localhost";
$dbname = "todolist";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Chyba připojení: " . $e->getMessage());
}

// === Zpracování formuláře (Uložení / Úprava) ===
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task = trim($_POST["task"] ?? "");
    $id = $_POST["id"] ?? null; // ID přítomno pouze při úpravě

    if ($task !== "" && mb_strlen($task) <= 50) {
        if ($id) {
            // UPDATE: Úprava existujícího úkolu
            $sql = "UPDATE tasks SET task = :task WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(["task" => $task, "id" => $id]);
        } else {
            // INSERT: Nový úkol
            $sql = "INSERT INTO tasks (task) VALUES (:task)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(["task" => $task]);
        }
        header("Location: index.php");
        exit();
    }
}

// === Akce GET (Smazání / Hotovo / Načtení pro editaci) ===
if (isset($_GET["delete"])) {
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
    $stmt->execute(["id" => $_GET["delete"]]);
    header("Location: index.php");
    exit();
}

if (isset($_GET["done"])) {
    $stmt = $pdo->prepare("UPDATE tasks SET done = 1 WHERE id = :id");
    $stmt->execute(["id" => $_GET["done"]]);
    header("Location: index.php");
    exit();
}

$taskToEdit = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
    $stmt->execute(["id" => $_GET["edit"]]);
    $taskToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// === Načtení všech úkolů ===
$tasks = $pdo->query("SELECT * FROM tasks ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="cs-cz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="styles/style.css">
    <title>ToDoList</title>
</head>
<body>
    <div class="form-container">
        <form method="post">
            <h1>Moje úkoly</h1>
            
            <?php if ($taskToEdit): ?>
                <input type="hidden" name="id" value="<?php echo $taskToEdit['id']; ?>">
            <?php endif; ?>

            <div class="input-box">
                <input type="text" name="task" class="input-add"
                       value="<?php echo $taskToEdit ? htmlspecialchars($taskToEdit['task']) : ''; ?>"
                       maxlength="50" placeholder="Jaký je Tvůj úkol?..."
                       autocomplete="off" required>
            </div>
            <button class="submit-btn" type="submit">
                <?php echo $taskToEdit ? 'Upravit úkol' : 'Přidat úkol'; ?>
            </button>
        </form>

        <div class="input-box">
            <h2>Seznam úkolů</h2>
        </div>
    
        <ul>
            <?php foreach ($tasks as $t): ?>
                <li>
                    <span class="task-text">
                        <?php if ($t['done']): ?>
                            <s><?php echo htmlspecialchars($t["task"]); ?></s>
                        <?php else: ?>
                            <?php echo htmlspecialchars($t["task"]); ?>
                        <?php endif; ?>
                    </span>

                    <span class="task-actions">
                        <a href="?delete=<?php echo $t["id"]; ?>" class="btn-icon delete"><i class="fa-solid fa-trash"></i></a>
                        <?php if (!$t['done']): ?>
                            <a href="?done=<?php echo $t["id"]; ?>" class="btn-icon done"><i class="fa-solid fa-check"></i></a>
                            <a href="?edit=<?php echo $t["id"]; ?>" class="btn-icon edit"><i class="fa-solid fa-pen"></i></a>
                        <?php endif; ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const input = document.querySelector(".input-add");
            if (input) input.focus();
        });
    </script>
</body>
</html>