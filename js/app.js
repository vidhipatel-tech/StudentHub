// Practical 4: JavaScript DOM, events and UI interactivity
(() => {
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

  // Theme switcher with localStorage.
  const themeButton = $('#themeButton');
  const savedTheme = localStorage.getItem('studenthub-theme');
  if (savedTheme === 'light') document.body.classList.add('light');
  const updateThemeIcon = () => { if (themeButton) themeButton.textContent = document.body.classList.contains('light') ? '☼' : '☾'; };
  updateThemeIcon();
  themeButton?.addEventListener('click', () => {
    document.body.classList.toggle('light');
    localStorage.setItem('studenthub-theme', document.body.classList.contains('light') ? 'light' : 'dark');
    updateThemeIcon();
  });

  // Hamburger menu and Hub dashboard dropdown.
  const hamburger = $('#hamburger');
  const topLinks = $('#topLinks');
  hamburger?.addEventListener('click', () => {
    const open = topLinks.classList.toggle('open');
    hamburger.setAttribute('aria-expanded', String(open));
  });
  const hubButton = $('#hubButton');
  const hubMenu = $('#hubMenu');
  hubButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    const open = hubButton.getAttribute('aria-expanded') === 'true';
    hubButton.setAttribute('aria-expanded', String(!open));
    hubMenu.hidden = open;
  });
  document.addEventListener('click', (event) => {
    if (hubMenu && !event.target.closest('.hub-menu-wrap')) { hubMenu.hidden = true; hubButton?.setAttribute('aria-expanded', 'false'); }
  });
  const sideHubBtn = $('#sideHubBtn');
  const sideHubMenu = $('#sideHubMenu');
  sideHubBtn?.addEventListener('click', () => {
    const open = sideHubMenu.classList.toggle('open');
    sideHubBtn.setAttribute('aria-expanded', String(open));
  });

  // Notification banner.
  const closeNotice = $('#closeNotice');
  closeNotice?.addEventListener('click', () => $('#noticeBanner')?.remove());

  // Password visibility.
  $$('[data-password-toggle]').forEach(button => {
    button.addEventListener('click', () => {
      const input = $('#' + button.dataset.passwordToggle);
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
      button.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
    });
  });

  // Small helper for inline form errors.
  const setError = (id, message) => { const el = document.querySelector(`[data-error-for="${id}"]`); if (el) el.textContent = message; };
  const clearErrors = root => $$('.error', root).forEach(el => { el.textContent = ''; });
  const emailOK = value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  const nameOK = value => /^[A-Za-z ]{2,50}$/.test(value.trim());
  const mobileOK = value => /^[6-9]\d{9}$/.test(value);
  const passwordOK = value => /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(value);

  // Practical 5: Registration form validation and password strength.
  const registrationForm = $('#registrationForm');
  const password = $('#regPassword');
  const strengthBar = $('#strengthBar');
  const strengthText = $('#strengthText');
  password?.addEventListener('input', () => {
    const value = password.value;
    let score = 0;
    if (value.length >= 8) score++;
    if (/[a-z]/.test(value)) score++;
    if (/[A-Z]/.test(value)) score++;
    if (/\d/.test(value)) score++;
    if (/[^A-Za-z\d]/.test(value)) score++;
    strengthBar.style.width = `${score * 20}%`;
    strengthText.textContent = ['Enter a password.', 'Very weak', 'Weak', 'Medium', 'Strong', 'Strong password'][score];
  });
  registrationForm?.addEventListener('submit', event => {
    event.preventDefault(); clearErrors(registrationForm); const name=$('#regName').value, email=$('#regEmail').value.trim(), mobile=$('#regMobile').value.trim(), pass=$('#regPassword').value, confirm=$('#regConfirm').value, course=$('#regCourse').value, year=$('#regYear').value, gender=$('input[name="gender"]:checked'), terms=$('#regTerms').checked; let ok=true;
    if(!nameOK(name)){setError('regName','Enter a valid name.');ok=false;} if(!emailOK(email)){setError('regEmail','Enter a valid email address.');ok=false;} if(!mobileOK(mobile)){setError('regMobile','Enter a valid 10-digit mobile number.');ok=false;} if(!course){setError('regCourse','Select your branch.');ok=false;} if(!year){setError('regYear','Select your year.');ok=false;} if(!gender){setError('gender','Select a gender.');ok=false;} if(!passwordOK(pass)){setError('regPassword','Use 8+ characters with upper, lower, number and special character.');ok=false;} if(pass!==confirm){setError('regConfirm','Passwords do not match.');ok=false;} if(!terms){setError('regTerms','Accept the confirmation before submitting.');ok=false;} if(ok) $('#registrationSuccess').hidden=false;
  });

  // Practical 4 + 6: Home content slider loaded from JSON.
  const homeSlider = $('#homeSlider');
  const sliderDots = $('#sliderDots');
  if (homeSlider) {
    fetch('data/events.json').then(r => { if(!r.ok) throw new Error('Events could not be loaded.'); return r.json(); }).then(events => {
      const featured = events.slice(0,6); let start=0;
      const render = () => {
        const visible = featured.slice(start,start+3); if(visible.length<3) visible.push(...featured.slice(0,3-visible.length));
        homeSlider.innerHTML = visible.map(e => `<article class="slider-slide"><img src="${e.image}" alt="${e.title}"><span class="course-code">${e.category}</span><h3>${e.title}</h3><p class="event-meta">${formatDate(e.date)} / ${e.venue}</p><button class="btn event-detail" data-event-id="${e.id}" type="button">View Details</button></article>`).join('');
        sliderDots.innerHTML = featured.slice(0,4).map((_,i)=>`<button class="slider-dot ${i===Math.floor(start/1)?'active':''}" data-slide="${i}" aria-label="Show featured event ${i+1}" type="button"></button>`).join('');
        $$('.event-detail',homeSlider).forEach(btn=>btn.addEventListener('click',()=>openEventModal(events.find(e=>e.id===Number(btn.dataset.eventId)))));
        $$('.slider-dot',sliderDots).forEach(dot=>dot.addEventListener('click',()=>{start=Number(dot.dataset.slide);render();}));
      };
      $('#sliderPrev')?.addEventListener('click',()=>{start=(start-1+featured.length)%featured.length;render();});
      $('#sliderNext')?.addEventListener('click',()=>{start=(start+1)%featured.length;render();});
      render();
    }).catch(() => { homeSlider.innerHTML='<div class="state-message">Events are currently unavailable.</div>'; });
  }

  // Practical 6: Events JSON fetch, search, filter, sort and pagination.
  const eventGrid = $('#eventGrid');
  if (eventGrid) {
    let allEvents=[], filtered=[], page=1; const perPage=6;
    fetch('data/events.json').then(async a=>{ if(!a.ok) throw new Error(); return a.json(); }).then(events=>{
      allEvents=events; filtered=[...events]; $('#eventLoading').hidden=true; const categories=[...new Set(events.map(e=>e.category))].sort(); $('#eventCategory').insertAdjacentHTML('beforeend',categories.map(c=>`<option>${c}</option>`).join('')); renderEvents();
    }).catch(()=>{ $('#eventLoading').textContent='Events could not be loaded. Please check the JSON file.'; });
    const apply=()=>{const q=$('#eventSearch').value.toLowerCase().trim(), cat=$('#eventCategory').value, sort=$('#eventSort').value; filtered=allEvents.filter(e=>(!q||`${e.title} ${e.category} ${e.venue} ${e.description}`.toLowerCase().includes(q))&&(cat==='all'||e.category===cat)); filtered.sort((a,b)=>sort==='title'?a.title.localeCompare(b.title):sort==='date-desc'?b.date.localeCompare(a.date):a.date.localeCompare(b.date));page=1;renderEvents();};
    $('#eventSearch')?.addEventListener('input',apply); $('#eventCategory')?.addEventListener('change',apply); $('#eventSort')?.addEventListener('change',apply);
    function renderEvents(){
      const start=(page-1)*perPage;
      const items=filtered.slice(start,start+perPage);
      eventGrid.innerHTML=items.map(e=>`<article class="event-card"><span class="event-number">${String(e.id).padStart(2,'0')} / ${e.category.toUpperCase()}</span><div class="event-image"><img src="${e.image}" alt="${e.title} poster"></div><h3>${e.title}</h3><p class="event-meta">${formatDate(e.date)} / ${e.venue}</p><p class="muted">${e.description}</p><button class="btn event-detail" data-event-id="${e.id}" type="button">View Details</button></article>`).join('');
      $('#eventEmpty').hidden=items.length!==0;
      $('#eventPagination').innerHTML=Array.from({length:Math.ceil(filtered.length/perPage)},(_,i)=>`<button class="${page===i+1?'active':''}" data-page="${i+1}" type="button">${i+1}</button>`).join('');
      $$('#eventPagination button').forEach(b=>b.addEventListener('click',()=>{page=Number(b.dataset.page);renderEvents();window.scrollTo({top:document.querySelector('.section').offsetTop-80,behavior:'smooth'});}));
      $$('.event-detail',eventGrid).forEach(btn=>btn.addEventListener('click',()=>openEventModal(allEvents.find(e=>e.id===Number(btn.dataset.eventId)))));
    }
  }

  // Modal popup used by Events and Home slider.
  function openEventModal(event){ if(!event) return; $('#eventModalImage').src=event.image; $('#eventModalImage').alt=event.title; $('#eventModalCategory').textContent=event.category; $('#eventModalTitle').textContent=event.title; $('#eventModalMeta').textContent=`${formatDate(event.date)} / ${event.time} / ${event.venue}`; $('#eventModalDescription').textContent=event.description; $('#eventModal').hidden=false; document.body.classList.add('modal-open'); }
  const modal=$('#eventModal'); $('#modalClose')?.addEventListener('click',()=>{modal.hidden=true;document.body.classList.remove('modal-open')}); modal?.addEventListener('click',e=>{if(e.target===modal){modal.hidden=true;document.body.classList.remove('modal-open')}}); document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal&&!modal.hidden){modal.hidden=true;document.body.classList.remove('modal-open')}});
  $('#eventRegisterBtn')?.addEventListener('click',()=>{alert('Event registration request has been recorded.');});

  // Practical 6: FAQ JSON fetch, rendering and search.
  const faqList=$('#faqList'); if(faqList){let faqData=[];fetch('data/faqs.json').then(r=>{if(!r.ok)throw new Error();return r.json()}).then(data=>{faqData=data;$('#faqLoading').hidden=true;renderFaqs();}).catch(()=>$('#faqLoading').textContent='FAQs could not be loaded.'); const renderFaqs=()=>{const q=$('#faqSearch').value.toLowerCase().trim();const items=faqData.filter(x=>!q||`${x.question} ${x.answer}`.toLowerCase().includes(q));faqList.innerHTML=items.map(x=>`<article class="faq-item"><button class="faq-question" type="button" aria-expanded="false">${x.question}<span>+</span></button><div class="faq-answer" hidden>${x.answer}</div></article>`).join('');$('#faqEmpty').hidden=items.length!==0;$$('.faq-question',faqList).forEach(btn=>btn.addEventListener('click',()=>{const answer=btn.nextElementSibling;const open=btn.getAttribute('aria-expanded')==='true';btn.setAttribute('aria-expanded',String(!open));btn.querySelector('span').textContent=open?'+':'−';answer.hidden=open;}));}; $('#faqSearch')?.addEventListener('input',renderFaqs);}

  // Practical 6: Student JSON is rendered in the Admin dashboard.
  const studentTable=$('#studentTable'); if(studentTable){let data=[];fetch('data/students.json').then(r=>{if(!r.ok)throw new Error();return r.json()}).then(rows=>{data=rows;$('#studentCount').textContent=rows.length;renderStudents();}).catch(()=>{studentTable.innerHTML='<tr><td colspan="6">Student records could not be loaded.</td></tr>'}); const renderStudents=()=>{const q=$('#studentSearch').value.toLowerCase().trim();const rows=data.filter(s=>`${s.id} ${s.name} ${s.email} ${s.course} ${s.year}`.toLowerCase().includes(q));studentTable.innerHTML=rows.map(s=>`<tr><td>${s.id}</td><td>${s.name}</td><td>${s.email}</td><td>${s.course}</td><td>${s.year}</td><td>${s.status}</td></tr>`).join('')||'<tr><td colspan="6">No students found.</td></tr>';};$('#studentSearch')?.addEventListener('input',renderStudents);}

  // Practical 5: Basic validation for contact, feedback and login forms.
  [['contactForm','contactSuccess',['contactName','contactEmail','contactMessage']],['feedbackForm','feedbackSuccess',['feedbackName','feedbackEmail','feedbackType','feedbackMessage']],['loginForm','loginSuccess',['loginEmail','loginPassword']]].forEach(([formId,successId,fields])=>{const form=$('#'+formId);if(!form)return;form.addEventListener('submit',e=>{e.preventDefault();clearErrors(form);let ok=true;fields.forEach(id=>{const el=$('#'+id);if(!el.value.trim()){setError(id,'This field is required.');ok=false;}});if((formId==='contactForm'||formId==='feedbackForm')&&$('#'+fields[1])?.value.trim()&&!emailOK($('#'+fields[1]).value.trim())){setError(fields[1],'Enter a valid email address.');ok=false;}if(ok)$('#'+successId).hidden=false;});});

  // Profile photo selection: fixed preview box, size validation and localStorage persistence.
  const photoInput=$('#profilePhotoInput'); const profileImage=$('#profileImage'); const profilePlaceholder=$('#profilePlaceholder'); const photoError=$('#photoError');
  $('#choosePhoto')?.addEventListener('click',()=>photoInput?.click());
  const storedPhoto=localStorage.getItem('studenthub-profile-photo'); if(storedPhoto&&profileImage){profileImage.src=storedPhoto;profileImage.hidden=false;profilePlaceholder.hidden=true;}
  photoInput?.addEventListener('change',()=>{const file=photoInput.files[0];photoError.textContent='';if(!file)return;if(file.size>2*1024*1024){photoError.textContent='Image must be 2 MB or smaller.';photoInput.value='';return;}if(!['image/jpeg','image/png','image/webp'].includes(file.type)){photoError.textContent='Choose a JPG, PNG or WebP image.';photoInput.value='';return;}const reader=new FileReader();reader.onload=()=>{profileImage.src=reader.result;profileImage.hidden=false;profilePlaceholder.hidden=true;localStorage.setItem('studenthub-profile-photo',reader.result);};reader.readAsDataURL(file);});
  $('#profileForm')?.addEventListener('submit',e=>{e.preventDefault();$('#profileSuccess').hidden=false;});

  function formatDate(value){const d=new Date(`${value}T00:00:00`);return d.toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'});}
})();
