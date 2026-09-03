<?php

namespace Mailbox\Libraries;

use Mailbox\Libraries\General_imap;
use Mailbox\Libraries\Outlook_imap;

class Imap {

    public function run_imap() {
        $Mailboxes_model = new \Mailbox\Models\Mailboxes_model();
        $options = array("authorized_imap_only" => true);
        $mailboxes = $Mailboxes_model->get_details($options)->getResult();

        foreach ($mailboxes as $mailbox) {

            if ($mailbox->imap_type === "general_imap") {
                $General_imap = new General_imap();
                $General_imap->process_emails($mailbox);
            } else {
                $Outlook_imap = new Outlook_imap($mailbox);
                $Outlook_imap->process_emails();
            }
        }
    }
}
