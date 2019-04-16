<?php

/*
Adminsidan kan skapa och ändra på biljetter.
*/

require_once(__DIR__."\classes\Database.php");
include(__DIR__."\includes\menu.php");

$pdo = Database::pdo();                             // skapar en databas-anslutning

echo("<h1>Admin - biljetter</h1>");

// Hämta alla events som finns i databasen som $EVENTS
$EVENTS = array();
$stmt = $pdo->prepare('
    SELECT
        id, name
    FROM tickets.event
');
$stmt->execute(array());

while( $event = $stmt->fetch() ) {              // varje rad ur databasen hämtas till $event
    $EVENTS[] = $event;                         // $event läggs till i slutet av $EVENTS (listan för alla events)
}

// Hämta alla kunder som finns i databasen som $CUSTOMERS
$CUSTOMERS = array();
$stmt = $pdo->prepare('
    SELECT
        id, name
    FROM tickets.customer
');
$stmt->execute(array());

while( $customer = $stmt->fetch() ) {
    $CUSTOMERS[] = $customer;
}

//om formulär skickats för ändring av biljetter, hantera formuläret
if ($_POST) {
    if (
        isset($_POST["action"])
    ) {
        if(
            $_POST["action"] == "save" &&  // en biljett ska ändras eller skapas
            isset($_POST["id"]) &&
            isset($_POST["event"]) &&
            isset($_POST["customer"])
        ) {
            if($_POST["id"] == "new") { // en ny biljett ska skapas
                $stmt = $pdo->prepare('
                    INSERT INTO tickets.ticket
                        (code, event, customer)
                    VALUES
                        (?, ?, ?)
                ');
                $code = substr(str_shuffle(md5(time())),0,10); // skapar en 10 tecken lång kod
                $stmt->execute(array($code, $_POST["event"], $_POST["customer"]));
            } else { // en biljett ska ändras
                $stmt = $pdo->prepare('
                    UPDATE tickets.ticket
                    SET
                        event=?,
                        customer=?
                    WHERE id=?
                ');
                $stmt->execute(array($_POST["event"], $_POST["customer"], $_POST["id"]));
            }
        } else if (
            $_POST["action"] == "remove" &&  // en biljett ska raderas
            isset($_POST["id"])
        ) {
            $stmt = $pdo->prepare('
                DELETE FROM tickets.ticket
                WHERE id=?
            ');
            $stmt->execute(array($_POST["id"]));
        }
    }

    echo("<meta http-equiv='refresh' content='0'>"); // efter formulär hanterats, skicka tillbaka att sidan ska laddas om
} else { // om inte formulär skickats ska en sida visas med en tabell för alla biljetter som finns i databasen
    ?>
    <table>
        <thead>
            <tr>
                <th>id</th>
                <th>event</th>
                <th>kund</th>
                <th>kod</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
    <?php
    
    // hämta alla biljetter ur databasen
    $stmt = $pdo->prepare('
    SELECT
        id, code, event, customer
    FROM tickets.ticket
    ');
    $stmt->execute(array());

    // loop över alla biljetter som gör en tabellrad med ett formulär
    while( $ticket = $stmt->fetch() ) { ?>
        <tr>
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <input type="text" style="display:none" name="id" value="<?php echo($ticket["id"]); ?>">
                <td>
                    <?php
                        echo(htmlspecialchars($ticket["id"]));
                    ?>
                </td>
                <td>
                    <!-- en rull-lista i formuläret för att ändra event -->
                    <select name="event">
                        <?php
                        // loopa igenom alla events för att hitta det event som redan ska vara vald
                        foreach($EVENTS as $event) {
                            $selected = "";
                            if($ticket["event"] == $event["id"]) {
                                $selected = "selected";
                            }
                        ?>
                            <option
                                value="<?php echo(htmlspecialchars($event["id"])); ?>"
                                <?php echo($selected); ?>
                            >
                                <?php echo(htmlspecialchars($event["name"])); ?>
                            </option>
                        <?php } ?>
                    </select>
                </td>
                <td>
                    <!-- en rull-lista i formuläret för att ändra kund -->
                    <select name="customer">
                        <?php
                        // loopa igenom alla kunder för att hitta den kund som redan ska vara vald
                        foreach($CUSTOMERS as $customer) {
                            $selected = "";
                            if($ticket["customer"] == $customer["id"]) {
                                $selected = "selected";
                            }
                        ?>
                            <option
                                value="<?php echo(htmlspecialchars($customer["id"])); ?>"
                                <?php echo($selected); ?>
                            >
                                <?php echo(htmlspecialchars($customer["name"])); ?>
                            </option>
                        <?php } ?>
                    </select>
                </td>
                <td>
                    <?php
                        echo(htmlspecialchars($ticket["code"]));
                    ?>
                </td>
                <td>
                    <button type="submit" name="action" value="save">Spara</button>
                    <button type="submit" name="action" value="remove">Ta bort</button>
                </td>
            </form>
        </tr>
    <?php
    }
    ?>
    <!-- efter tabellen med alla biljetter skapas en till tabellrad för att kunna lägga til nya biljetter -->
    <tbody>
        <tfoot>
            <tr>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <input type="text" style="display:none" name="id" value="new">
                    <td></td>
                    <td>
                        <select name="event">
                            <option value="" selected></option>
                            <?php foreach($EVENTS as $event) { ?>
                                <option
                                    value="<?php echo(htmlspecialchars($event["id"])); ?>"
                                >
                                    <?php echo(htmlspecialchars($event["name"])); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                    <td>
                        <select name="customer">
                            <option value="" selected></option>
                            <?php foreach($CUSTOMERS as $customer) { ?>
                                <option
                                    value="<?php echo(htmlspecialchars($customer["id"])); ?>"
                                >
                                    <?php echo(htmlspecialchars($customer["name"])); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                    <td></td>
                    <td>
                        <button type="submit" name="action" value="save">Spara</button>
                    </td>
                </form>
            </tr>
        </tfoot>
    </table>
    <?php
}
?>