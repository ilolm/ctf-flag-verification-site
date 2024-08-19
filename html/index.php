<?php
session_start();
include('config.php');

if (!isset($_SESSION['flags_status'])) {
    $_SESSION['flags_status'] = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $submitted_flag = $_POST['flag'] ?? '';
    $name = $_POST['name'] ?? '';

    if (!empty($submitted_flag) && !empty($name)) {
        $submitted_flag = trim($submitted_flag);
        $hashed_flag = md5($submitted_flag);

        $stmt = $conn->prepare("SELECT name FROM flags WHERE flag=? AND name=?");
        $stmt->bind_param("ss", $hashed_flag, $name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['flags_status'][$name] = $submitted_flag;
        } else {
            $_SESSION['flags_status'][$name] = 'incorrect';
        }

        $stmt->close();
    }
}

$conn->close();

$all_correct = count(array_filter($_SESSION['flags_status'], fn($flag) => $flag !== 'incorrect')) === 6;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bungee+Tint&family=Teko:wght@400&display=swap" rel="stylesheet">
    <title>CTF Flag Verification</title>
    <style>
        body {
            background-color: #1e1e1e;
            color: #c7c7c7;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background-color: rgba(40, 44, 52, 0.8);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.6);
            max-width: 600px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .flag-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            width: 100%;
        }

        .flag-title {
            flex-basis: 35%;
            font-size: 18px;
            text-align: right;
            padding-right: 20px;
            color: #61dafb;
        }

        .flag-input {
            flex-basis: 65%;
            display: flex;
            align-items: center;
            position: relative;
        }

        .flag-input input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 2px solid #c7c7c7;
            border-radius: 8px;
            background-color: #2c2c2c;
            color: #c7c7c7;
            margin-right: 10px;
            transition: border-color 0.3s, background-color 0.3s;
        }

        .flag-input input.correct {
            border-color: #98c379;
            background-color: #3b3b3b;
            color: #c7c7c7;
        }

        .flag-input input.incorrect {
            border-color: #e06c75;
            background-color: #3b3b3b;
            color: #c7c7c7;
        }

        .flag-input button {
            padding: 10px 20px;
            border: none;
            background-color: #61dafb;
            color: #1e1e1e;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s, transform 0.2s;
        }

        .flag-input button:hover {
            background-color: #4aa9db;
        }

        .flag-input button:active {
            transform: scale(0.95);
        }

        .loading {
            display: none;
            position: absolute;
            right: 50px;
            width: 20px;
            height: 20px;
            border: 3px solid transparent;
            border-top-color: #61dafb;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .correct .loading {
            border-top-color: #98c379;
        }

        .incorrect .loading {
            border-top-color: #e06c75;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .congratulation-box {
            display: <?php echo $all_correct ? 'block' : 'none'; ?>;
            background-color: #98c379;
            color: #1e1e1e;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 400;
            text-align: center;
            width: 100%;
            font-size: 1.5em;
        }

        .congratulation-box .text-part-1 {
            font-family: 'Teko', sans-serif;
            font-size: 1.2em;
        }

        .congratulation-box .text-part-2 {
            font-family: 'Bungee Tint', sans-serif;
            font-size: 2em;
            margin-top: 10px;
        }
    </style>
    <script>
        function showLoading(button, input) {
            const parent = button.parentElement;
            const loading = parent.querySelector('.loading');
            loading.style.display = 'block';
            setTimeout(() => {
                loading.style.display = 'none';
                if (input.classList.contains('correct')) {
                    loading.classList.add('correct');
                } else {
                    loading.classList.add('incorrect');
                }
            }, 1000);
        }
    </script>
</head>
<body>
    <div class="container">
        <?php
        $names = ['DB password', 'user.txt', 'gleb.txt', 'rebeca.txt', 'docker-root.txt', 'root.txt'];
        foreach ($names as $name) {
            $flag_value = $_SESSION['flags_status'][$name] ?? '';
            $readonly = !empty($flag_value) && $flag_value !== 'incorrect' ? 'readonly' : '';
            $input_class = '';
            if (!empty($flag_value)) {
                $input_class = $flag_value === 'incorrect' ? 'incorrect' : 'correct';
                $flag_value = $flag_value === 'incorrect' ? '' : $flag_value;
            }
        ?>
        <form method="POST" class="flag-row" onsubmit="showLoading(this.querySelector('button'), this.querySelector('input[type=text]'));">
            <div class="flag-title"><?php echo $name; ?></div>
            <div class="flag-input">
                <input type="text" name="flag" value="<?php echo htmlspecialchars($flag_value); ?>" class="<?php echo $input_class; ?>" placeholder="Enter flag" required <?php echo $readonly; ?>>
                <input type="hidden" name="name" value="<?php echo $name; ?>">
                <button type="submit" <?php echo $readonly; ?>>Submit</button>
                <div class="loading"></div>
            </div>
        </form>
        <?php } ?>

        <div class="congratulation-box">
            <span class="text-part-1">
                My congrads to you with passing this CTF.<br>I spent so many hours to make all of those)
            </span>
            <h1 class="text-part-2">
                Welcome to Hacker's Community!
            </h1>
        </div>
    </div>
</body>
</html>
