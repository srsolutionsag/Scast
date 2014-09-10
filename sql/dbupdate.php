<#1>
<?php
$fields = array(
  'id' => array(
    'type' => 'integer',
    'length' => 4,
    'notnull' => true
  ),
  'ext_id' => array(
    'type' => 'text',
    'length' => 254,
    'fixed' => false,
    'notnull' => false
  )
);

$ilDB->createTable("rep_robj_xsca_data", $fields);
$ilDB->addPrimaryKey("rep_robj_xsca_data", array("id"));
?>
<#2>
<?php
$fields = array(
  'user_id' => array(
    'type' => 'integer',
    'length' => 4,
    'notnull' => true
  ),
  'clip_ext_id' => array(
    'type' => 'text',
    'length' => 254,
    'fixed' => false,
    'notnull' => false
  )
);

$ilDB->createTable("rep_robj_xsca_cmember", $fields);
$ilDB->addPrimaryKey("rep_robj_xsca_cmember", array("user_id","clip_ext_id"));
?>
<#3>
<?php

if(!$ilDB->tableColumnExists('rep_robj_xsca_data', 'is_ivt'))
{
	$ilDB->addTableColumn(
		'rep_robj_xsca_data',
		'is_ivt',
		array(
			'type'         => 'integer', 
			'length'         => 1,
			'default'        => 0
		)
	);
}


?>
<#4>
<?php

if(!$ilDB->tableColumnExists('rep_robj_xsca_data', 'inviting'))
{
    $ilDB->addTableColumn(
        'rep_robj_xsca_data',
        'inviting',
        array(
            'type'         => 'integer',
            'length'         => 1,
            'default'        => 0
        )
    );
}
?>
<#5>
<?php

if(!$ilDB->tableColumnExists('rep_robj_xsca_data', 'castsysaccount'))
{
    $ilDB->addTableColumn(
        'rep_robj_xsca_data',
        'castsysaccount',
        array(
            'type' => 'text',
            'length' => 254,
            'fixed' => false,
            'notnull' => false
        )
    );
}
?>
<#6>
<?php
if(!$ilDB->tableExists('rep_robj_xsca_conf'))
{
    $fields = array(
        'name' => array(
            'type' => 'text',
            'length' => 254,
            'fixed' => false,
            'notnull' => true
        ),
        'value' => array(
            'type' => 'text',
            'length' => 254,
            'fixed' => false,
            'notnull' => false
        )
    );
    $ilDB->createTable("rep_robj_xsca_conf", $fields);
}
?>
<#7>
<?php
if(!$ilDB->tableExists('rep_robj_xsca_group'))
{
    $fields = array(
        'id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'scast_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'title' => array(
            'type' => 'text',
            'length' => 254,
            'fixed' => false,
            'notnull' => false
        )
    );

    $ilDB->createTable("rep_robj_xsca_group", $fields);
    $ilDB->addPrimaryKey("rep_robj_xsca_group", array("id"));
    $ilDB->createSequence("rep_robj_xsca_group");
}
if(!$ilDB->tableExists('rep_robj_xsca_grp_usr'))
{
    $fields = array(
        'group_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'usr_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        )
    );
    $ilDB->createTable("rep_robj_xsca_grp_usr", $fields);
}
?>
<#8>
<?php
if(!$ilDB->tableColumnExists('rep_robj_xsca_data', 'introduction_text'))
{
    $ilDB->addTableColumn(
        'rep_robj_xsca_data',
        'introduction_text',
        array(
            'type' => 'text',
            'length' => 4000,
            'fixed' => false,
            'notnull' => false
        )
    );
}
?>
<#9>
<?php
$ilDB->addPrimaryKey("rep_robj_xsca_conf", array("name"));
?>
<#10>
<?php
$ilDB->query("ALTER TABLE rep_robj_xsca_data CHANGE castsysaccount organization_domain VARCHAR( 254 )");
?>
<#11>
<?php
if(!$ilDB->tableColumnExists('rep_robj_xsca_data', 'is_online'))
{
    $ilDB->addTableColumn(
        'rep_robj_xsca_data',
        'is_online',
        array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => false
        )
    );
}
?>
<#12>
<?php
    $ilDB->manipulateF('UPDATE rep_robj_xsca_data SET is_online = %s',
        array('integer'),
        array(1));
?>
<#13>
<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Api/class.xscaApiCache.php');
xscaApiCache::installDB();
?>
<#14>
<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Api/class.xscaApiCache.php');
xscaApiCache::updateDB();
?>
<#15>
<?php
	if(!$ilDB->tableColumnExists('rep_robj_xsca_data', 'show_upload_token'))
	{
		$ilDB->addTableColumn(
			'rep_robj_xsca_data',
			'show_upload_token',
			array(
				'type' => 'integer',
				'length' => 1,
				'notnull' => false
			)
		);
	}
?>

