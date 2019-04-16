<?php

/*
Denna fil/sida gör två saker:
1. hanterar köp-formuläret som skickas från index.php
2. visar kundens alla köpta biljetter med deras koder
*/

require_once(__DIR__."\classes\Database.php");
include(__DIR__."\includes\menu.php");

$pdo = Database::pdo();

echo("<h1>Besökare - biljetter</h1>");

//om formulär skickats är det ett köp som ska genomföras, hantera formuläret
if ($_POST) {
    if (                                    // kolla att alla data som behövs finns i $_POST
        isset($_POST["action"]) &&
        $_POST["action"] == "buy" &&
        isset($_POST["events"]) &&
        isset($_POST["name"]) &&
        isset($_POST["city"]) &&
        isset($_POST["address"]) &&
        isset($_POST["consent"]) &&
        $_POST["consent"] == "consent"
    ) {
        // skapa en ny kund i databasen
        $stmt = $pdo->prepare('
            INSERT INTO tickets.customer
                (name, city, address)
            VALUES
                (?, ?, ?)
        ');
        $success = $stmt->execute(array($_POST["name"], $_POST["city"], $_POST["address"]));
        if($success) {                                      // om det gick att skapa en ny kund i databasen ska biljetter skapas
            $customerId = $pdo->lastInsertId();             // hämtar id från kunden som skapades i databasen
            $events = explode(":", $_POST["events"]);
            foreach ($events as $eventId) {
                $stmt = $pdo->prepare('
                    INSERT INTO tickets.ticket
                        (code, event, customer)
                    VALUES
                        (?, ?, ?)
                ');
                $code = substr(str_shuffle(md5(time())),0,10); // skapar en 10 tecken lång kod
                $stmt->execute(array($code, $eventId, $customerId));
            }
            // efter att alla biljetter skapats så ska en länk för att se biljettkodenra visas
            ?>
            Tack för ditt köp!
            <br>
            <a href="<?php echo($_SERVER['PHP_SELF']."?customer=".$customerId); ?>">Visa biljetter</a> <!-- länken för att se biljetternas koder leder tillbaka till den här sidan -->
            <?php
        } else {
            ?>
            Något gick fel
            <?php
        }

        
    }
} else if ($_GET) { //om det finns ett kundnummer i urlen ($_GET) ska biljetterna visas
    if(isset($_GET["customer"])) {
        // hämta ut alla biljetter för en viss kund
        $stmt = $pdo->prepare('
        SELECT
            event,
            code,
            customer,
            event.name AS event_name
        FROM tickets.ticket
        LEFT JOIN
            tickets.event ON tickets.event.id = event
        WHERE customer=?
        ');
        $stmt->execute(array($_GET["customer"]));
    
        while( $ticket = $stmt->fetch() ) {                     // loopar igenom alla biljetter och visar koderna
            echo(htmlspecialchars($ticket["event_name"]));
            echo(", kod: ");
            echo(htmlspecialchars($ticket["code"]));
            echo("<br>");
        }
    }
    
}