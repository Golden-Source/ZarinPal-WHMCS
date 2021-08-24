<div class="linkbar">
    <ul class="nav nav-pills">
        <li class="<?= ($vars['selectedTab'] == 'transactions' ? 'active' : null); ?>">
            <a href="addonmodules.php?module=ZarinpalAddon&amp;tab=transactions">
                تراکنش ها
            </a>
        </li>
        <li class="<?= ($vars['selectedTab'] == 'settings' ? 'active' : null); ?>">
            <a href="addonmodules.php?module=ZarinpalAddon&amp;tab=settings">
                تنظیمات
            </a>
        </li>
    </ul>
</div>
<br/>

<style>
    .module-adminarea {
        position: relative;
        min-height: 100vh;
    }

    .module-adminarea.rtl {
        direction: rtl;
        text-align: right;
    }

    .module-adminarea.rtl .nav {
        padding-right: 0;
    }

    .module-adminarea .copyright {
        direction: ltr;
        text-align: center;
        font-size: 12px;
        position: absolute;
        bottom: 0;
        width: 100%;
        padding-top: 1em;
        border-top: 1px solid #dee2e6;
    }


    .module-adminarea.rtl .nav-pills>li {
        float: right;
    }

    .module-adminarea .wrap {
        margin-top: 2.5em;
        padding-bottom: 60px;
    }

    .linkbar {
        padding-bottom: 5px;
        border-bottom: 2px solid #6CAD41;
    }
</style>

