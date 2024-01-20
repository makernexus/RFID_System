// OVLcheckinout.js
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2024 Maker Nexus
// By Jim Schrempp
//


document.addEventListener('DOMContentLoaded', function(){
    // after the page is loaded

    // Add a listener to the form to intercept the submit event
    document.getElementById('checkinform').addEventListener('submit', function(event) {
        event.preventDefault();

        // Check if names are set
        if (!this.elements['nameFirst'].value || !this.elements['nameLast'].value) {
            alertUser5seconds("First and last name are required.", "red");
            return;
        };
        if(this.elements['nameFirst'].value.trim() === "" || this.elements['nameLast'].value.trim() === "") {
            alertUser5seconds("First and last name are required.", "red");
            return;
        };
        // Check if "hasSignedWaiver" is set
        if (!this.elements['hasSignedWaiver'].value) {
            alertUser5seconds("You must answer the Signed Waiver question.", "red");
            return;
        }
    
        // get the form data
        var formData = new FormData(this);
    
        // Send the form data to the server
        fetch('/v1/OVLcheckinout.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => console.log(data))
        .catch((error) => console.error('Error:', error));

        // Clear the form
        this.reset();

        alertUser5seconds("You have been checked in. Thank you.", "green");

    });
});

function alertUser5seconds(message, color) {
    var alertBox = document.createElement('div');
    alertBox.textContent = message;
    alertBox.style.position = 'fixed';
    alertBox.style.left = '50%';
    alertBox.style.top = '50%';
    alertBox.style.transform = 'translate(-50%, -50%)';
    alertBox.style.backgroundColor = color;
    alertBox.style.color = 'white';
    alertBox.style.padding = '20px';
    alertBox.style.zIndex = '1000';
    document.body.appendChild(alertBox);

    setTimeout(function() {
        alertBox.remove();
    }, 5000);

    return;
}
