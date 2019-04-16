<?php

/*
Denna sida visar alla events och en varukorg som sparas med en cookie.
*/

require_once(__DIR__."\classes\Database.php");
include(__DIR__."\includes\menu.php");

$pdo = Database::pdo();     // Här connectar jag till databasen genom classen Database 

echo("<h1>Besökare - köp/varukorg</h1>");

echo("<h2>Köp</h2>");
// Hämta alla events och visa dem
$stmt = $pdo->prepare('
    SELECT
        id, name
    FROM tickets.event
');
$stmt->execute();
?>
<table>
    <tbody>
    <?php
    while( $event = $stmt->fetch() ) {                  //Loopa igenom alla rader från databasen//
        ?>
        <tr data-id="<?php echo(htmlspecialchars($event["id"])); ?>" >
            <td><?php echo(htmlspecialchars($event["name"])); ?></td>
            <td><button onclick="addToCart(this)">lägg till</button></td>
        </tr>
        <?php
    }
    ?>
    </tbody>
</table>

<h2>Varukorg <button onclick='clearCart()'>töm</button></h2>
<!-- en tabell med alls som ligger i varukorgen, uppdateras med js -->
<table>
    <tbody id="cartTable">
        <tr id="cartTemplate">
            <td class="name"></td>
            <td>
                <button class="remove" onclick="removeFromCart(this)">ta bort</button>
            </td>
        </tr>
    </tbody>
</table>

<!-- Formuläret för att köpa de event som finns i varukorgen -->
<form method="post" action="/classicmodels/viktor-eget-projekt/buy.php">
    <input type="text" name="events" value="" id="buyEventIds" style="display:none"> <!-- ett osynligt fält som ska innehålla alla event-idn som man vill köpa, uppdateras med js -->
    <br>
    <input type="text" name="name" value="" placeholder="Namn" required>
    <br>
    <input type="text" name="city" value="" placeholder="Stad" required>
    <br>
    <input type="text" name="address" value="" placeholder="Gata" required>
    <br>
    <input type="checkbox" required name="consent" value="consent" id="consent"><label for="consent">Jag accepterar användaravtalet</label>
    <br>
    <button type="submit" name="action" value="buy">Köp</button>
</form>

<?php
// Skapa en array med id och namn på alla events för att ge till javascript.
$event_names = [];
$stmt = $pdo->prepare('
    SELECT
        id, name
    FROM tickets.event
    '
);
$stmt->execute(array());
while( $event = $stmt->fetch() ) {
    $event_names[htmlspecialchars($event["id"])] = htmlspecialchars($event["name"]); 
}
?>

<script>

var EVENT_NAMES = <?php echo json_encode($event_names); ?>;    // Echoa en Php array som en javascript
var CART_ROW = document.getElementById("cartTemplate").cloneNode(true);   // Hämta och kopiera raden till js
CART_ROW.id = "";   

window.onload = updateCart();


function addToCart(button) {                            // Lägg till i varukorgen
    var parent = button.parentNode.parentNode;
    var id = parent.getAttribute("data-id");            // Id på det event som ska hamna i varukorgen

    var eventIds = getCookie("events");                 // Hämta cookien med alla events redan i varukorgen
    if(eventIds != "") {                                
        eventIds += ":"
    }
    eventIds += id;
    setCookie("events", eventIds, 2);                   // Spara till cookien
    updateCart();
}

/*
    Funktionen updateCart uppdaterar html-tabellen som visar vad som finns i kundkorger.
        1. hämtar alla events från coockien
        2. rensar html-tabellen
        3. lägger till alla events från coockien i tabellen som rader
        4. uppdaterar köp-fomruläret med data från coockien
*/
function updateCart() {
    var eventIds = getCookie("events").split(":");                  // hämtar alla events från cookien som en lista
    var cartTable = document.getElementById("cartTable");           // varukorgs-tabellen
    
    // tömmer varukorgstabellen
    while (cartTable.firstChild) {                                  // while-loopen körs tills tabellen inte har något kvar i sig (tills firstChild inte längre finns)
        cartTable.removeChild(cartTable.firstChild);
    }

    // skapar nya rader i varukorgstabellen för alla saker event som fanns i cookien
    for (var i = 0; i < eventIds.length; i++) {                     // for-loopen går igenom alla events som finns i coockien
        var eventId = eventIds[i];
        
        if(eventId != "") {                                         // if-sats som kollar att event-id inte är en tom text, då borde har något gått fel och den borde inte finnas i varukorgen (behövs nog inte)
            var row = CART_ROW.cloneNode(true);                     // CART_ROW är en klon på en tabellrad som fanns i tabellen från början
            
            row.setAttribute("cart-id", i);                         // den nya tabellraden får ett "cart-id"
            row.getElementsByClassName("name")[0].innerHTML = EVENT_NAMES[eventId];         // den nya tabellraden får eventets namn i den tabell-cell som har klassen "name"
            
            cartTable.appendChild(row);                             // den nya tabellraden läggs till i varukorgstabellen
        }
    }

    var formField = document.getElementById("buyEventIds");
    formField.value = getCookie("events");                          // köp-formuläret får alla events som finns i cookien
}

/*
    Funktionen removeFromCart tar bort den sak man klickat på från varukorgen.
        1. från button (den knapp som klickats på) hämtas vilket event man vill ta bort
        2. eventen i cookien hämtas som en lista och det event man klickat på tas ut ur listan
        3. eventen sparas till coockien igen
        4. updateCart körs för att uppdatera tabellen över vad som ligger i varukorgen
*/
function removeFromCart(button) {
    var row = button.parentNode.parentNode;
    var cartId = row.getAttribute("cart-id");

    var eventIds = getCookie("events").split(":");
    eventIds.splice(cartId, 1);                             // tar bort eventet i listan eventIds
    eventIds = eventIds.join(":");                          // lägger ihop listan av event till en text som kan sparas i coockien
    
    setCookie("events", eventIds, 2);                       // den nya listan av events sparas i coockien
    updateCart();
}

/*
    Funktionen clearCart tömmer varukorgen.
*/
function clearCart() {
    setCookie("events", "", 2);                             // Skriver en tom text ("") till coockien så att alla events försvinner därifrån
    updateCart();                                           // kör update cart så att varukorgs-tabellen uppdateras
}

/*
Funktion för att spara och läsa cookies. 
taget från: https://www.w3schools.com/js/js_cookies.asp
*/

/*
    setCoockie sparar data till en cookie
*/
function setCookie(cname, cvalue, exdays) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
  var expires = "expires="+d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

/*
    getCoockie hämtar data från en cookie
*/
function getCookie(cname) {
  var name = cname + "=";
  var ca = document.cookie.split(';');
  for(var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

</script>