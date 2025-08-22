<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>403 &mdash; Stisla</title>

  <!-- General CSS Files -->
  <link rel="stylesheet" href="{{ asset('assets/modules/bootstrap/css/bootstrap.min.css')  }}">
  <link rel="stylesheet" href="{{ asset('assets/modules/fontawesome/css/all.min.css')  }}">

  <!-- CSS Libraries -->

  <!-- Template CSS -->
  <link rel="stylesheet" href="{{ asset('assets/css/style.css')  }}">
  <link rel="stylesheet" href="{{ asset('assets/css/components.css')  }}">
</head>

<body>
  <div id="app">
    <section class="section">
      <div class="container mt-5">
        <div class="page-error">
          <div class="page-inner">
            <h1>403</h1>
            <div class="page-description">
            	You do not have access to this page.
            </div>
            <div class="page-search">
              <div class="mt-3">
                <a href="{{ route('login') }}">Back to Home</a>
              </div>
            </div>
          </div>
        </div>
        <div class="simple-footer mt-5">
            Copyright &copy; 2024 PT. Ecogreen Oleochemicals
        </div>
      </div>
    </section>
  </div>

  <!-- General JS Scripts -->
  <script src="{{ asset('assets/modules/jquery.min.js')  }}"></script>
  <script src="{{ asset('assets/modules/popper.js')  }}"></script>
  <script src="{{ asset('assets/modules/tooltip.js')  }}"></script>
  <script src="{{ asset('assets/modules/bootstrap/js/bootstrap.min.js')  }}"></script>
  <script src="{{ asset('assets/modules/nicescroll/jquery.nicescroll.min.js')  }}"></script>
  <script src="{{ asset('assets/modules/moment.min.js')  }}"></script>
  <script src="{{ asset('assets/js/stisla.js')  }}"></script>

  <!-- JS Libraies -->

  <!-- Page Specific JS File -->

  <!-- Template JS File -->
  <script src="{{ asset('assets/js/scripts.js')  }}"></script>
  <script src="{{ asset('assets/js/custom.js')  }}"></script>
</body>
</html>
