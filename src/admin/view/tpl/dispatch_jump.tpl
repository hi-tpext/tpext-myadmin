<!DOCTYPE html>
<html lang="zh">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <title>{:__admin_lang('redirect_title')}</title>
  <meta name="author" content="yinqi">
  <link href="/assets/lightyearadmin/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/lightyearadmin/css/materialdesignicons.min.css" rel="stylesheet">
  <link href="/assets/lightyearadmin/css/style.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #fff;
    }

    .lyear-layout-content {
      padding: 0;
    }

    .card
    {
      margin-top: 20px;
    }

    .alert-info
    {
      margin-top:30px;
    }

    .card-body
    {
      margin-top : 30px;
    }
    
    .powerd
    {
      position : fixed;
      bottom : 10px;
      width : 100%;
      padding : 0;
      margin : 0;
      font-size : 12px;
    }

  </style>
</head>

<body>
  <div class="lyear-layout-web">
    <div class="lyear-layout-container">
      <!--页面主要内容-->
      <main class="lyear-layout-content">
        <div class="container-fluid">
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4>{:__admin_lang('redirect_title')}</h4>
                </div>
                <div class="card-body text-center">
                  <?php switch ($code) {?>
                  <?php case 1:?>
                  <div class="alert alert-success " role="alert">
                    <?php echo(strip_tags($msg));?>
                  </div>
                  <?php break;?>
                  <?php case 0:?>
                  <div class="alert alert-danger " role="alert">
                    <?php echo(strip_tags($msg));?>
                  </div>
                  <?php break;?>
                  <?php } ?>

                  <p>{:__admin_lang('page_auto_redirect')}<b id="wait"><?php echo($wait);?></b></p>

                  <div class="alert alert-info" role="alert">
                    <a id="href" href="<?php echo($url);?>" class="btn btn-danger btn-w-xl">{:__admin_lang('redirect_btn')}</a>
                    <a onclick="history.go(-1)" class="btn btn-default btn-w-xl">{:__admin_lang('go_back')}</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="powerd text-center"><span>{:__admin_lang('powered_by')}</span></div>
        </div>
      </main>
    </div>
  </div>

  <script type="text/javascript">
    (function () {
      var wait = document.getElementById('wait'),
        href = document.getElementById('href').href;
      var interval = setInterval(function () {
        var time = --wait.innerHTML;
        if (time <= 0) {
          location.href = href;
          clearInterval(interval);
        };
      }, 1000);
    })();
  </script>
</body>

</html>