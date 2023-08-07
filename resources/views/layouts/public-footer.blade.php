<footer class="footer p-10 bg-base-300 text-base-content">
  <div>
    <span class="footer-title">Acerca de</span> 
    <a class="link link-hover" href="{{ route('public.about') }}">Sobre nosotros</a> 
    <a class="link link-hover" href="{{ route('public.contact') }}">Contáctanos</a> 
    <span>Hecho con <i class="fa-solid fa-heart text-red-500"></i> en El Salvador</span>
  </div> 
  <div>
    <span class="footer-title">Legal</span> 
    <a class="link link-hover" href="{{ route('public.privacy') }}">Política de privacidad</a> 
    <a class="link link-hover" href="{{ route('public.terms') }}">Términos y condiciones</a> 
  </div> 
  <div>
    <span class="footer-title">Social</span> 
    <div class="grid grid-flow-col gap-4 text-3xl">
      <a href="https://linkedin.com/company/padiushbio" target="_blank">
        <i class="fa-brands fa-linkedin"></i>
      </a>
    </div>
  </div>
</footer>