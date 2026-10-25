<?php

/**
 * Admin panel switches.
 */
return [

    /**
     * Permanent deletes (enquiries, experiences & add-ons, website list items). Off for now: the delete
     * buttons are hidden and delete requests are refused. Hiding, "Active" switches and closing enquiries
     * still work. Set ADMIN_DELETES_ENABLED=true in .env to bring the buttons back.
     */
    'deletes_enabled' => (bool) env('ADMIN_DELETES_ENABLED', false),

];
