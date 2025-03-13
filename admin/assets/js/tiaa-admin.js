window.onload = function() {
// Attach click event listeners to each item
// pings// Select all elements with the class 'tiaa-ping-discourse-class'
    const clickableItemsPing = document.querySelectorAll('.tiaa-ping-discourse-class');
    const clickableItemsMessage = document.querySelectorAll('.tiaa-message-discourse-class');
    clickableItemsPing.forEach(item => {
        let divID;
        divID = item.id;
        let anchorID = document.getElementById(divID + "-a");
        anchorID.addEventListener('click', function (event) {
            event.preventDefault();
//            console.log("href:" + event.target.href)
            fetchPingResults(event.target.href, event);

        });
    });
// messages
    clickableItemsMessage.forEach(item => {
        let divID;
        divID = item.id;
        // console.log('setting: ' + divID);
        let anchorID = document.getElementById(divID + "-a");
        anchorID.addEventListener('click', function (event) {
            event.preventDefault();
//        alert('Message was clicked!' + event.target.href);
            fetchMessage(event.target.href, event);
//        alert('after fetch')

        });
    });
    const screenManageEmailButton = document.getElementById('tiaaManageScreenedEmails');
    const screenEmailDiv = document.getElementById('tiaaScreenedEmailsDiv');

    screenManageEmailButton.addEventListener('click', (event) => {
        event.preventDefault();
        const isExpanded = screenEmailDiv.style.display === 'block';

        screenEmailDiv.style.display = isExpanded ? 'none' : 'block';
        screenManageEmailButton.setAttribute('aria-expanded', !isExpanded); // Update for screen readers
    });
};

function fetchPingResults(ref, event) {
    fetch(ref)
        .then(response => response.json()) // Parsing the data as JSON
        .then(data => {
 //           console.log(data); // Logging data to the console
            let anchorID = event.target.id;
            let divResultsID = anchorID.replace(/-a$/,'-results');
            let divResults = document.getElementById(divResultsID);
            if (divResults) {
                // setting CSS values based on the data obtained
                divResults.style.display = 'inline-block';
                if (data['status'] === 200) {
                    divResults.style.color = 'green'
                    divResults.innerHTML = 'success'
                } else {
                    divResults.style.color = 'red'
                    let obj;
                    try {
                        obj = JSON.parse(data['body_response']);
//                        console.log('obj: ' + obj);
                        divResults.innerHTML = data['status'] + ': ' + data['response'] + ' - ' + obj.errors;
                    } catch (error) {
                        // handle error in parsing JSON
                        console.log(`JSON parse error - `);
                        console.log(error);
                        divResults.innerHTML = 'Error processing your request...';
                    }
                }
            }
        })
        .catch((error) => {
            console.log(error.message);
            // TODO - add something meaningful to the user...
        });
}

function fetchMessage(ref, event) {
    fetch(ref)
        .then(response => response.json()) // Parsing the data as JSON
        .then(data => {
            //                      console.log(data); // Logging data to the console
            let anchorID = event.target.id;
            let divResultsID = anchorID.replace(/-a$/,'-results');
            let divResults = document.getElementById(divResultsID);
            if (divResults) {
                // setting CSS values based on the data obtained
                divResults.style.display = 'block';
                if (data['status'] === 200) {
                    let payload = JSON.parse(data['body_response']);
                    let message = getMessageFromPost(payload['cooked']);
                    if (message.length < 10) {
                        divResults.style.color = 'red';
                        divResults.innerHTML = "Post doesn't contain 'Begin Message ----' tag at start of message.<br>" +
                            payload['cooked']
                        ;

                    } else {
                        divResults.style.color = 'green';
                        console.log(message);
                        divResults.innerHTML = message;
                    }
                } else {
                    divResults.style.color = 'red';
                    let obj;
                    try {
                        obj = JSON.parse(data['body_response']);
//                        console.log('obj: ' + obj);
                        divResults.innerHTML = data['status'] + ': ' + data['response'] + ' - ' + obj.errors;
                    } catch (error) {
                        // handle error in parsing JSON
                        console.log(`JSON parse error - ${error.message}`);
                        divResults.innerHTML = 'Error processing your request...';
                    }
                }
            }
        })
        .catch((error) => {
            console.log(error.message);
            // TODO - add something meaningful to the user...
        });
}
function getMessageFromPost(post) {
    // console.log("in getMessageFromPost" + post)
    if (post.length < 10) {
        return '';
    }
    const beginRegX = /\nBeginMessage ----<br>\n/;
    startIndex = post.search(beginRegX);
    if (startIndex > 0){
        let message = '<p>' + post.substring(startIndex + (beginRegX.toString().length - 4));
        return(message);
    } else {
        return('');
    }

}
