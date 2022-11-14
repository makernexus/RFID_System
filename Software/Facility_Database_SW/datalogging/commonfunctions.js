// javascript functions to run in the client web pages
// 
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp

function checkout(clientID,name) {
    var xhttp = new XMLHttpRequest();
    var theURL = "http://rfid.makernexuswiki.com/rfidmanualcheckout.php?clientID=" + clientID 
        + "&firstName=" + encodeURI(name);
    xhttp.open("GET", theURL, true);
    xhttp.send();
    location.reload(true);
}

// -----------------
//  Show / hide
//
function showHideDiv(divID) {
    var x = document.getElementById(divID);
    if (x.style.display === "none") {
        x.style.display = "block";
    } else {
        x.style.display = "none";
    }
}
