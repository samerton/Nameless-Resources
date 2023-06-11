<?php
/**
 * Resources moduke - default constants
 *
 * @author Samerton
 * @license MIT
 */

const RESOURCES_AUTHOR = '/author';

const RESOURCES_CATEGORY = '/category';

const RESOURCES_DOWNLOAD = '/download';

const RESOURCES_ROOT = '/resources';

const RESOURCES_RESOURCE = '/resource';

const RESOURCES_PURCHASE = '/purchase';

const RESOURCES_ICON_DEFAULT = 'uploads/resources_icons/default.png';

const RESOURCES_PRE_PURCHASE_DEFAULT = <<<STR
<p>
You will be redirected to an external gateway to complete your purchase.
</p>
<p>
Access to the download will only be granted once the payment has been completed, this may take a while.
</p>
<p>
Please note, {{siteName}} cannot take any responsibility for purchases that occur through our resources section.
</p>
<p>
If you experience any issues with the resource, please contact the resource author directly.
</p>
<p>
If your access to {{siteName}} is revoked (for example, your account is banned), you will lose access to any purchased resources.
</p>
STR;
