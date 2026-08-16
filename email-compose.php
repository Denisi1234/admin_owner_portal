<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/dz.php';

$db = getDbConnection();
$userRole = $_SESSION['user_role'] ?? 'admin';
$userId = $_SESSION['user_id'] ?? ($userRole === 'admin' ? 1 : 2);
$userEmail = $_SESSION['user_email'] ?? 'admin@fastnet.com';
$api_token = $_SESSION['api_token'] ?? '';

$notice = '';

// Handle sending a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipient_id']) && isset($_POST['text'])) {
    $recipientId = intval($_POST['recipient_id']);
    $text = trim($_POST['text']);
    $lodgeName = $_POST['lodge_name'] ?? 'FastNet Support';

    if (!empty($text) && $recipientId > 0) {
        $ch = curl_init("http://127.0.0.1:8000/api/messages");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'recipient_id' => $recipientId,
            'lodge_name' => $lodgeName,
            'text' => $text,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$api_token}"
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 201 || $code === 200) {
            $notice = "Message sent successfully!";
        } else {
            // Local DB fallback insert
            $stmt = $db->prepare("INSERT INTO messages (sender_id, recipient_id, lodge_name, text, unread, created_at, updated_at) VALUES (?, ?, ?, ?, 1, datetime('now'), datetime('now'))");
            $stmt->execute([$userId, $recipientId, $lodgeName, $text]);
            $notice = "Message sent!";
        }
    }
}

// Fetch messaging partners list (If Admin -> fetch Owners; If Owner -> fetch Admin and support users)
if ($userRole === 'admin') {
    $partners = $db->query("SELECT id, name, email, role FROM users WHERE role = 'owner' ORDER BY name ASC")->fetchAll();
} else {
    $partners = $db->query("SELECT id, name, email, role FROM users WHERE role = 'admin' ORDER BY name ASC")->fetchAll();
}

$selectedPartnerId = isset($_GET['partner_id']) ? intval($_GET['partner_id']) : (!empty($partners) ? $partners[0]['id'] : 0);

// Fetch conversation thread history with selected partner
$conversation = [];
if ($selectedPartnerId > 0) {
    $stmt = $db->prepare("
        SELECT m.*, u.name as sender_name 
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$userId, $selectedPartnerId, $selectedPartnerId, $userId]);
    $conversation = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<title>Messages & Support Center | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
	<?php include 'elements/meta.php';?>
	<link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
	<?php include 'elements/page-css.php'; ?>
    <style>
        .chat-history-container {
            height: 420px;
            overflow-y: auto;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
        }
        .msg-bubble-sent {
            background: #135846;
            color: #fff;
            border-radius: 16px 16px 0 16px;
            padding: 12px 18px;
            max-width: 70%;
            margin-left: auto;
            margin-bottom: 12px;
        }
        .msg-bubble-recv {
            background: #e9ecef;
            color: #212529;
            border-radius: 16px 16px 16px 0;
            padding: 12px 18px;
            max-width: 70%;
            margin-right: auto;
            margin-bottom: 12px;
        }
    </style>
</head>

<body>
    <?php include 'elements/pre-loader.php'; ?>

    <div id="main-wrapper">
        <?php include 'elements/nav-header.php'; ?>
        <?php include 'elements/chatbox.php'; ?>
        <?php include 'elements/header.php'; ?>
        <?php include 'elements/sidebar.php'; ?>

        <div class="content-body">
            <div class="container-fluid">
				
				<div class="row page-titles">
					<ol class="breadcrumb">
						<li class="breadcrumb-item active"><a href="javascript:void(0)"><?php echo ucfirst($userRole); ?> Portal</a></li>
						<li class="breadcrumb-item"><a href="javascript:void(0)">Messages & Direct Communication</a></li>
					</ol>
                </div>

                <?php if (!empty($notice)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($notice); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
				
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-0">
                                <div class="row g-0">
                                    
                                    <!-- Contacts & Threads Directory (Left Sidebar) -->
                                    <div class="col-md-4 border-end p-4">
                                        <h5 class="font-w600 text-dark mb-3">
                                            <i class="fas fa-comments me-2 text-primary"></i>
                                            <?php echo ($userRole === 'admin') ? 'Lodge Owners Directory' : 'Admin & Support Desk'; ?>
                                        </h5>
                                        <div class="list-group list-group-flush">
                                            <?php if (empty($partners)): ?>
                                                <div class="text-muted fs-13 py-3">No contacts available.</div>
                                            <?php else: ?>
                                                <?php foreach ($partners as $p): ?>
                                                    <?php $activeClass = ($p['id'] == $selectedPartnerId) ? 'active bg-primary text-white' : ''; ?>
                                                    <a href="email-compose.php?partner_id=<?php echo $p['id']; ?>" class="list-group-item list-group-item-action d-flex align-items-center p-3 mb-2 rounded border <?php echo $activeClass; ?>">
                                                        <div class="avatar avatar-md me-3 rounded-circle bg-light d-flex align-items-center justify-content-center fw-bold text-dark" style="width:40px; height:40px;">
                                                            <?php echo strtoupper(substr($p['name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <strong class="d-block mb-0"><?php echo htmlspecialchars($p['name']); ?></strong>
                                                            <span class="fs-12 opacity-75"><?php echo htmlspecialchars($p['email']); ?></span>
                                                        </div>
                                                    </a>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Chat Conversation Window (Right Area) -->
                                    <div class="col-md-8 p-4">
                                        <?php 
                                            $activePartner = null;
                                            foreach ($partners as $p) {
                                                if ($p['id'] == $selectedPartnerId) { $activePartner = $p; break; }
                                            }
                                        ?>

                                        <?php if ($activePartner): ?>
                                            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                                                <div>
                                                    <h5 class="font-w600 text-dark mb-0"><?php echo htmlspecialchars($activePartner['name']); ?></h5>
                                                    <span class="fs-12 text-muted"><?php echo htmlspecialchars($activePartner['email']); ?> (<?php echo ucfirst($activePartner['role']); ?>)</span>
                                                </div>
                                                <span class="badge bg-success py-2 px-3"><i class="fas fa-circle me-1 fs-10"></i> Direct Channel</span>
                                            </div>

                                            <!-- Chat History -->
                                            <div class="chat-history-container mb-3" id="chatWindow">
                                                <?php if (empty($conversation)): ?>
                                                    <div class="text-center text-muted py-5">
                                                        <i class="far fa-paper-plane fs-30 mb-2 d-block"></i>
                                                        No message history yet. Send a message to initiate discussion.
                                                    </div>
                                                <?php else: ?>
                                                    <?php foreach ($conversation as $msg): ?>
                                                        <?php $isMe = ($msg['sender_id'] == $userId); ?>
                                                        <div class="<?php echo $isMe ? 'msg-bubble-sent' : 'msg-bubble-recv'; ?>">
                                                            <div class="fs-14"><?php echo nl2br(htmlspecialchars($msg['text'])); ?></div>
                                                            <div class="fs-10 text-end opacity-75 mt-1"><?php echo htmlspecialchars($msg['created_at'] ?? 'Just now'); ?></div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Send Message Input Form -->
                                            <form method="POST" action="email-compose.php?partner_id=<?php echo $selectedPartnerId; ?>">
                                                <input type="hidden" name="recipient_id" value="<?php echo $selectedPartnerId; ?>">
                                                <input type="hidden" name="lodge_name" value="FastNet Communication">
                                                <div class="input-group">
                                                    <textarea name="text" class="form-control border style-1" rows="2" placeholder="Type your message to <?php echo htmlspecialchars($activePartner['name']); ?>..." required></textarea>
                                                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-1"></i> Send</button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <div class="text-center text-muted py-5">Select a contact from the left panel to begin messaging.</div>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?php include 'elements/footer.php'; ?>
    </div>

    <?php include 'elements/page-js.php'; ?>
    <script>
        // Auto scroll chat window to bottom
        const chatWin = document.getElementById('chatWindow');
        if (chatWin) chatWin.scrollTop = chatWin.scrollHeight;
    </script>
</body>
</html>