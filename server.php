<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $pdo;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        // Initialize the PDO connection
        $this->pdo = new \PDO('mysql:host=localhost;dbname=chat_app', 'root', '');
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    
        // Fetch and send chat history to the new connection
        $stmt = $this->pdo->query("SELECT * FROM messages ORDER BY timestamp DESC");
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $conn->send($row['message']);
        }
    
        echo "New connection! ({$conn->resourceId})\n";
    }    

    public function onMessage(ConnectionInterface $from, $msg) {
        $stmt = $this->pdo->prepare("INSERT INTO messages (message, sender) VALUES (?, ?)");
        $stmt->execute([$msg, $from->resourceId]);

        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Create the WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

$server->run();