    </div><!-- .content -->
  </div><!-- .main -->
</div><!-- .layout -->

<script>
document.addEventListener('click', e => {
  if (window.innerWidth <= 900 && !e.target.closest('.sidebar, .mobile-toggle')) {
    document.getElementById('sidebar').classList.remove('open');
  }
});
</script>
</body>
</html>
