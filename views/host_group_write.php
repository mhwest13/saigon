<?php
//
// Copyright (c) 2013, Zynga Inc.
// https://github.com/zynga/saigon
// Author: Matt West (https://github.com/mhwest13)
// License: BSD 2-Clause
//

require HTML_HEADER;

?>
<body>
<div class="divCacGroup admin_box_bg admin_box_blue admin_border_black">
Successfully wrote Host Group Information for <?php echo $viewData->host?> in Deployment <?php echo $viewData->deployment?> to datastore...
<div class="divCacGroup"></div>
<a href="action.php?controller=hostgrp&action=stage&deployment=<?php echo $viewData->deployment?>" class="deployBtn">Host Groups</a>
</div>
<?php

require HTML_FOOTER;
