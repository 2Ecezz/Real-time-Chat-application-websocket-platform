document.addEventListener('DOMContentLoaded', function() {
    var ws = new WebSocket('ws://localhost:8080');
    
    var chatHistory = document.getElementById('chat-history');
    var messageInput = document.getElementById('message');
    var sendButton = document.getElementById('send');

    ws.onmessage = function(event) {
        var msg = document.createElement('div');
        msg.textContent = event.data;
        chatHistory.appendChild(msg);
        chatHistory.scrollTop = chatHistory.scrollHeight; // Auto-scroll to bottom
    };

    sendButton.addEventListener('click', function() {
        var message = messageInput.value;
        if (message) {
            ws.send(message);
            messageInput.value = '';
        }
    });

    messageInput.addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            sendButton.click();
        }
    });
});
