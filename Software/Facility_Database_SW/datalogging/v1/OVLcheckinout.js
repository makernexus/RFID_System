// OVLcheckinout.js
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2024 Maker Nexus
// By Jim Schrempp
//


document.addEventListener('DOMContentLoaded', function(){
    // after the page is loaded

    // Add a listener to the form to intercept the submit event
    document.getElementById('checkinform').addEventListener('submit', function(event) {
        // Prevent the default form submit
        event.preventDefault();

        // Check if names are set
        nameFirstArray = document.getElementsByName('nameFirst[]');
        nameLastArray = document.getElementsByName('nameLast[]');
        if(nameFirstArray[0].value.trim() === "" || nameLastArray[0].value.trim() === "") {
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
            alertUser("You have been checked in. Thank you.", "green", 2000);
            window.location.href = "https://makernexus.org";
        }

    });

    // Add a listener to the "Add a Person" button
    document.querySelector("#addAPerson").addEventListener("click", function() {
       
        // Get the form
        var divTarget = document.querySelector("#extraPeople");

        var numPeopleField = document.querySelector("#numPeople");
        var numPeople = parseInt(numPeopleField.value) + 1;

        // Create a new div to hold the input fields
        var divNew = document.createElement("div");
        divNew.className = "extraPersonInput";

        // nameFirst
        // Create the nameFirst input field div
        var nameFirstDiv = document.createElement("div");
        nameFirstDiv.className = "extraPersonField";

        // Create the nameFirst imput field
        var input1 = document.createElement("input");
        input1.className = "inputField, extraPersonField";
        input1.type = "text";
        input1.name = "nameFirst[]";
        input1.id = "nameFirst" + numPeople.toString();

        // Create a label for the nameFirst input field
        var label1 = document.createElement("label");
        label1.className = "inputField, extraPersonField";
        label1.for = "nameFirst" + numPeople.toString();
        label1.textContent = "First Name:";

        // add to the div
        nameFirstDiv.appendChild(label1);
        nameFirstDiv.appendChild(input1);

        // nameLast
        // Create the nameLast input field div
        var nameLastDiv = document.createElement("div");
        nameLastDiv.className = "extraPersonField";

        // Create the nameLast input field
        var input2 = document.createElement("input");
        input2.className = "inputField, extraPersonField";
        input2.type = "text";
        input2.name = "nameLast[]";
        input2.id = "nameLast" + numPeople.toString();

        // Create a label for nameLast input field
        var label2 = document.createElement("label");
        label2.className = "inputField, extraPersonField";
        label2.for = "nameLast" + numPeople.toString();
        label2.textContent = "Last Name:";
        label2.style.marginLeft = "10px";

        // add to the div
        nameLastDiv.appendChild(label2);
        nameLastDiv.appendChild(input2);

        // Add the input fields to the new div
        divNew.appendChild(nameFirstDiv);
        divNew.appendChild(nameLastDiv);

        // Add the new div to the form
        divTarget.appendChild(divNew);

        // Update the number of people
        numPeopleField.value = numPeople;
    });


});

// Function to display an alert message for a short time
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
