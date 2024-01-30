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
            alertUser("First and last name are required.", "red");
            return;
        };
        if(this.elements['nameFirst'].value.trim() === "" || this.elements['nameLast'].value.trim() === "") {
            alertUser("First and last name are required.", "red");
            return;
        };
        // Check if "hasSignedWaiver" is set
        if (!this.elements['hasSignedWaiver'].value) {
            alertUser("You must answer the Signed Waiver question.", "red");
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

        if (this.elements['hasSignedWaiver'].value == 0 ) {
            // Clear the form
            this.reset();
            alertUser("You will now go to the waiver page.", "blue",1000)
            window.location.href = "https://app.waiversign.com/e/6421facd543e76001945bf5c/doc/6421fb7d543e76001945c3cb?event=none";
        } else {
            // Clear the form
            this.reset();
            alertUser("You have been checked in. Thank you.", "green");
            echo("");
        }

    });

    var button = document.querySelector("#addAPerson");

    button.addEventListener("click", function() {
       
        // Get the form
        var divTarget = document.querySelector("#extraPeople");

        var numPeopleField = document.querySelector("#numPeople");
        var numPeople = parseInt(numPeopleField.value) + 1;

        var divNew = document.createElement("div");
        divNew.style.marginTop = "20px";

        // Create the first input field
        var input1 = document.createElement("input");
        input1.type = "text";
        input1.name = "first_name[]";
        input1.id = "first_name" + numPeople.toString();

        // Create the second input field
        var input2 = document.createElement("input");
        input2.type = "text";
        input2.name = "last_name[]";
        input2.id = "last_name" + numPeople.toString();

        // Create a label for the first input field
        var label1 = document.createElement("label");
        label1.for = "first_name" + numPeople.toString();
        label1.textContent = "First Name:";

        // Create a label for the first input field
        var label2 = document.createElement("label");
        label2.for = "last_name" + numPeople.toString();
        label2.textContent = "Last Name:";
        label2.style.marginLeft = "10px";

        // Add the input fields to the form
        divNew.appendChild(label1);
        divNew.appendChild(input1);
        divNew.appendChild(label2);
        divNew.appendChild(input2);
        divTarget.appendChild(divNew);
        numPeopleField.value = numPeople;
    });


});

function alertUser(message, color, showForMS=5000) {
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
    }, showForMS);

    return;
}
