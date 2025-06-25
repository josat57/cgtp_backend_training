<?php

    echo "<!DOCTYPE html>
    <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>User Dashboard</title>
            <link rel='stylesheet' href='assets/style.css'>
        </head>
        <body>
            <main class='main-container'>
                <header class='main-header'>
                    <div class='brand'>
                        <h3 class='title'>MeetMe()</h3>
                    </div>
                    <nav class='nav-bar'>
                        <ul class='nav-list'>
                            <li class='list-items'><a href='profile.php'>Profile</a></li>
                            <li class='list-items'><a href='settings.php'>Settings</a></li>
                            <li class='list-items'><a href='logout.php'>Logout</a></li>
                        </ul>
                    </nav>
                </header>
                <div class='page-container'>
                    <aside class='side-bar'>
                        <section class='profile-details'>
                            <img src='assets/user.png' alt='User Avatar' class='user-avatar'>
                            <h2 class='sidebar-title'>Hi, {$data->first_name}</h2>
                            <p class='sidebar-title'>{$data->email}</p>
                        </section>
                        <section class='menu-section'>
                            <ul class='sidebar-menu'>
                                <li class='menu-item'><a href='dashboard.php'>Overview</a></li>
                                <li class='menu-item'><a href='profile.php'>Profile</a></li>
                                <li class='menu-item'><a href='settings.php'>Account</a></li>
                                <li class='menu-item'><a href='notifications.php'>Notifications</a></li>
                                <li class='menu-item'><a href='settings.php'>Settings</a></li>
                                <li class='menu-item'><a href='help.php'>Help & Support</a></li>
                            </ul>
                        </section>
                        <section class='sidebar-footer'>
                            <button class='logout-button'>Logout</button>
                        </section>
                    </aside>
                    <section class='page-content'>
                        <div class='card'>
                            <h1 class='card-title'>Introduction</h1>
                            <p>Hi, {$data->first_name} Welcome to your <span class='brand-name'>MeetMe</span>!</p>
                            <p>Here you can manage your account settings, view your profile, and more.</p>
                            <p>Feel free to explore the features available to you.</p>
                            <p>Most importantly, you can connect with others and make new friends every now and then!</p>
                        </div>
                    </section>
                </div>
                <footer class='page-footer'>
                    <p>&copy; 2023 Your Company. All rights reserved.</p>
                    <p>Contact us at <a href=''>https://meetme.com</a>:
                </footer>
            </main>
        </body>
    </html>
    ";

// echo $dashboardContent;