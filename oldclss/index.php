<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            background: linear-gradient(45deg, #1a1a1a, #333);
            color: #fff;
        }
        .header-title {
            font-size: 24px;
            text-align: center;
            margin-bottom: 20px;
            color: #3a053a;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: 'Roboto', 'Courier New', Courier, monospace;
            padding: 15px  10px;
            border-bottom: 2px solid #3a053a;
            width: 100%;
        }
        .main-container {
            width:100%;
            height: 100vh;
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: center;
            justify-content: center;
            background: url('images/bg_one.jpeg');
            background-size: cover;
            object-fit: cover;
            background-position: center;
            background-repeat: no-repeat;
            backdrop-filter: blur(5px);
            overflow: hidden;
        }
        .header{
            padding: 8px;
            margin: 0 10px;
            color: #ccc;
            font-weight: bold;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 10;
            padding: 30px 30px;
            align-items: center;
            justify-content: center;
            position: relative;
            border-radius: 10px;
            width: 400px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: solid 1px rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }
        input {
            padding: 15px 10px;
            border-radius: 10px;
            outline: none;
            border: solid 1px #ccc;
            margin-bottom: 10px;
            width: 250px;
        }
        input:focus {
            box-sizing: border-box;
            box-shadow: rgba(0px 1px 2px, 0.0.0.5);
            border: none;
        }

        .footer {
            display: flex;
            gap:10px;
            margin-top:15px;
            width: 100%;
            justify-content: end;
            align-items: center;
            padding: 15px  10px;
            border-top: 2px solid #3a053a;
            
        }
        button {
            padding: 10px 20px;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: cneter;
            color: #fff;
            font-size: 14px;
            outline: none;
            border: none;
        }

        button.hover {
            border: solid 2px #ccc;
        }

        .reg {
            background:#3a053a;
        }

    </style>
</head>
<body>
    <main class="main-container">
        <form action="register.php" method="POST">
            <h1 class="header-title">Register On Meet Me</h1>
            <input type="text" name="first_name" placeholder="Enter FirstName">
            <input type="text" name="last_name" placeholder="Enter LastName">
            <input type="email" name="email" placeholder="Enter Email">
            <input type="tel" name="phone" placeholder="Enter Phone">
            <input type="password" name="password" placeholder="Enter Password">
            <div class="footer">
                <button class="cancel">Cancel</button>
                <button class="reg">Register</button>
            </div>
        </form>
    </main>
</body>
</html>