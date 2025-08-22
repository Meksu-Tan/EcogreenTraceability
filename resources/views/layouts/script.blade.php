  <script src="{{ asset('assets/modules/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/modules/popper.js') }}"></script>
  <script src="{{ asset('assets/modules/tooltip.js') }}"></script>
  <script src="{{ asset('assets/modules/bootstrap/js/bootstrap.min.js') }}"></script>
  <script src="{{ asset('assets/modules/nicescroll/jquery.nicescroll.min.js') }}"></script>
  <script src="{{ asset('assets/modules/moment.min.js') }}"></script>
  <script src="{{ asset('assets/js/stisla.js') }}"></script>
  <!-- JS Libraies -->
  <script src="{{ asset('assets/modules/chart.min.js') }}"></script>
  <script src="{{ asset('assets/modules/jqvmap/dist/jquery.vmap.min.js') }}"></script>
  <script src="{{ asset('assets/modules/jqvmap/dist/maps/jquery.vmap.world.js') }}"></script>
  <script src="{{ asset('assets/modules/summernote/summernote-bs4.js') }}"></script>
  <script src="{{ asset('assets/modules/chocolat/dist/js/jquery.chocolat.min.js') }}"></script>

  <!-- Template JS File -->
  <script src="{{ asset('assets/js/scripts.js') }}"></script>
  <script src="{{ asset('assets/js/custom.js') }}"></script>

  <!-- Custom js -->
  <script src="{{ asset('assets/modules/sweetalert2/dist/sweetalert2.min.js') }}"></script>

  <script src="{{ asset('assets/modules/datatables-2.2.5/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('assets/modules/datatables-2.2.5/dataTables.bootstrap4.min.js') }}"></script>
  <script src="{{ asset('assets/modules/select2/js/select2.min.js') }}"></script>
  <script src="{{ asset('assets/modules/jstree/jstree.min.js') }}"></script>
  <script src="{{ asset('assets/modules/numerals/numerals.min.js') }}"></script>
  <script src="{{ asset('assets/js/custom-jstree.js') }}"></script>
  <script src="{{ asset('assets/modules/Handlebars-4.1.2/handlebars.min.js') }}"></script>

  @stack('js')

  @yield('script')
