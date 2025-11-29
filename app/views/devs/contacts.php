<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Contact — Simple Layout</title>
  <style>
    :root{
      --bg-top:#7ea57e;
      --bg-bottom:#dfe9df;
      --text:#111;
    }
    html,body{height:100%;margin:0;font-family: Georgia, 'Times New Roman', Times, serif;color:var(--text)}
    .page{
      min-height:100%;
      display:flex;
      align-items:center;
      justify-content:center;
      background:linear-gradient(180deg,var(--bg-top) 0%, #b9c9b9 35%, var(--bg-bottom) 100%);
      padding:40px 20px;
      box-sizing:border-box;
    }
    .card{
      width:100%;
      max-width:780px;
      background:transparent; /* image shows transparent center */
      padding:40px 30px;
      position:relative;
      text-align:center;
    }
    /* small circular logo at top-left inside the card */
    .logo{
      position:absolute;
      left:18px;
      top:18px;
      width:86px;
      height:86px;
      border-radius:50%;
      overflow:hidden;
      box-shadow:0 2px 6px rgba(0,0,0,0.08);
      background:linear-gradient(#cfe9d0,#9fc39a);
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .logo svg{width:80px;height:80px;display:block}

    h1.contact-line{
      margin:18px 0 18px;
      font-size:26px;
      font-weight:700;
      letter-spacing:0.5px;
    }
    p.contact-line{
      margin:18px 0;
      font-size:22px;
      font-weight:700;
    }
    /* make the email a bit narrower line-height like the picture */
    .small{font-size:20px}

    /* responsive tweaks */
    @media (max-width:560px){
      .logo{left:12px;top:12px;width:64px;height:64px}
      h1.contact-line{font-size:20px}
      p.contact-line{font-size:18px}
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="card">
      <div class="logo" aria-hidden="true">
        <!-- simple landscape SVG to mimic the round icon -->
        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden>
          <defs>
            <linearGradient id="g1" x1="0" x2="0" y1="0" y2="1">
              <stop offset="0" stop-color="#bfe6ff" />
              <stop offset="1" stop-color="#d6f1d8" />
            </linearGradient>
          </defs>
          <rect width="100" height="100" fill="url(#g1)" />
          <circle cx="22" cy="20" r="6" fill="#ffffff" opacity="0.9" />
          <path d="M0 70 C25 55,35 65,60 55 L100 80 L0 80 Z" fill="#0f3" opacity="0.9"/>
          <path d="M0 80 L100 80 L100 100 L0 100 Z" fill="#0b6"/>
        </svg>
      </div>

      <div class="content">
        <h1 class="contact-line">Main Hotline: (02) 1234-5678</h1>
        <p class="contact-line">Mobile: 0917-123-4567</p>
        <p class="contact-line small">Email: agrisystem.com</p>
        <p class="contact-line">Address: San Pablo City Laguna</p>
      </div>
    </div>
  </div>
</body>
</html>