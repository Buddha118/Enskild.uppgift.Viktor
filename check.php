<?php

/*
Den här sidan kan visa ifall en biljettkod är giltig eller inte.
*/

require_once(__DIR__."\classes\Database.php");
include(__DIR__."\includes\menu.php");

$pdo = Database::pdo();

echo("<h1>Inläsning - giltighet</h1>");

// formulär för att kolla giltighet av kod, formuläret skickas till den här PHP-sidan
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <input type="text" name="code" value="">
    <br>
    <button type="submit" name="action" value="save">Undersök giltighet</button>
</form>
<?php

//om formulär skickats, hantera formuläret
if ($_POST) {
    if (isset($_POST["code"]) && $_POST["code"] !== "") { // om kod skickats i formuläret hämta biljett och kund samt event info
        // hämta biljetten från den givna koden tillsammans med data från kunden
        $stmt = $pdo->prepare('
            SELECT
                event,
                customer,
                event.name AS event_name,
                customer.name AS customer_name,
                customer.address AS customer_address,
                customer.city AS customer_city
            FROM tickets.ticket
            LEFT JOIN
                tickets.event ON tickets.event.id = event
            LEFT JOIN
                tickets.customer ON tickets.customer.id = customer
            WHERE code=?
        ');
        $stmt->execute(array($_POST["code"]));

        $ticket = NULL;

        // Loopa igenom alla rader från databasen och uppdatera $ticket varje gång.
        // Förhoppningsvis finns det bara en biljett att hämta.
        // (om det finns flera biljetter med samma kod kommer bara den senaste att visas på sidan)
        while( $row = $stmt->fetch() ) {
           $ticket = $row;
        }

        if($ticket !== NULL) { // Bilgetten giltig! (det fanns en biljett i databasen)
            echo("<code>".htmlspecialchars($_POST["code"])."</code> <strong>giltig</strong>");
            echo("<br>");
            echo(htmlspecialchars($ticket["event_name"]));
            echo("<br>");
            echo(htmlspecialchars($ticket["customer_name"]));
            echo("<br>");
            echo(htmlspecialchars($ticket["customer_address"]));
            echo("<br>");
            echo(htmlspecialchars($ticket["customer_city"]));
        } else { // Bilgetten inte giltigt! (det fanns ingen biljett i databasen)
            echo( htmlspecialchars($_POST["code"])." <strong>ej giltig</strong>");
        }

    }

}
?>