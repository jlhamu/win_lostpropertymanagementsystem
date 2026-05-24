<?php
/**
 * Email Functions - Wentworth Lost and Found Management System
 * Uses PHPMailer. Install via: composer require phpmailer/phpmailer
 */

require_once BASE_PATH . 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email notifying a user that a potential match was found for their lost item.
 *
 * @param  string $toEmail   Recipient email address
 * @param  string $toName    Recipient full name
 * @param  array  $lostItem  The matched lost item row from DB
 * @param  array  $foundItem The newly reported found item row from DB
 * @return bool   True on success, false on failure
 */
function sendMatchNotification(
    string $toEmail,
    string $toName,
    array  $lostItem,
    array  $foundItem
): bool {

    $mail = new PHPMailer(true);

    try {
        // ── SMTP Configuration ──────────────────────────────────────────────
        // Change these values to match your email provider.
        // For Gmail: enable 2FA and create an App Password at
        //   https://myaccount.google.com/apppasswords
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';          // SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com';    // Your email
        $mail->Password   = 'your-app-password';       // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        // ────────────────────────────────────────────────────────────────────

        $mail->setFrom('noreply@win.edu.au', 'Wentworth Lost and Found');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Possible Match Found for Your Lost Item';

        // Safe variables for HTML body
        $safeToName   = htmlspecialchars($toName);
        $safeLostName = htmlspecialchars($lostItem['item_name']);
        $safeFoundName= htmlspecialchars($foundItem['item_name']);
        $safeCategory = htmlspecialchars($foundItem['category']);
        $safeLocation = htmlspecialchars($foundItem['location']);
        $safeDate     = htmlspecialchars($foundItem['date_reported']);
        $itemLink     = BASE_URL . 'item-detail.php?id=' . (int)$foundItem['item_id'];

        $mail->Body = "
        <html>
        <body style='font-family:Arial,sans-serif;color:#222;max-width:600px;margin:0 auto;'>
          <div style='background:#1f4e79;color:white;padding:25px;text-align:center;'>
            <h1 style='margin:0;'>Wentworth Lost &amp; Found</h1>
            <p style='margin:5px 0 0;opacity:.85;'>Wentworth Higher Institute of Education</p>
          </div>
          <div style='padding:30px;'>
            <p>Dear <strong>{$safeToName}</strong>,</p>
            <p>Great news! A possible match has been found for your lost item:
               <strong>{$safeLostName}</strong>.</p>

            <div style='background:#f4f7fb;padding:20px;border-left:4px solid #f5b041;margin:25px 0;border-radius:6px;'>
              <h3 style='color:#1f4e79;margin-top:0;'>Found Item Details</h3>
              <p style='margin:6px 0;'><strong>Item Name:</strong> {$safeFoundName}</p>
              <p style='margin:6px 0;'><strong>Category:</strong> {$safeCategory}</p>
              <p style='margin:6px 0;'><strong>Location Found:</strong> {$safeLocation}</p>
              <p style='margin:6px 0;'><strong>Date Found:</strong> {$safeDate}</p>
            </div>

            <p>Please login to the system to view the item details and submit an ownership claim.</p>

            <p style='text-align:center;margin:30px 0;'>
              <a href='{$itemLink}'
                 style='background:#1f4e79;color:white;padding:13px 28px;text-decoration:none;
                        border-radius:6px;display:inline-block;font-weight:bold;'>
                View Found Item &rarr;
              </a>
            </p>

            <p style='color:#666;font-size:.9rem;'>
              If this is not your item, you can ignore this email.
            </p>
          </div>
          <div style='background:#f4f7fb;padding:18px;text-align:center;color:#666;font-size:.85rem;'>
            <p>Regards,<br><strong>Wentworth Lost and Found Team</strong><br>
               Wentworth Higher Institute of Education</p>
          </div>
        </body>
        </html>";

        $mail->AltBody =
            "Dear {$safeToName},\n\n"
            . "A possible match has been found for your lost item: {$safeLostName}.\n\n"
            . "Found Item Details:\n"
            . "  Item:     {$safeFoundName}\n"
            . "  Category: {$safeCategory}\n"
            . "  Location: {$safeLocation}\n"
            . "  Date:     {$safeDate}\n\n"
            . "Login to view it: {$itemLink}\n\n"
            . "Regards,\nWentworth Lost and Found Team";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("PHPMailer error: {$mail->ErrorInfo}");
        return false;
    }
}
