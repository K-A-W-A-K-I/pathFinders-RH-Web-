<?php

namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/formations', name: 'client_')]
class PresentationController extends AbstractController
{
    private const GROQ_API_KEY = '';
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const VOICERSS_KEY = '3c1a419a9c314d7ba9c86c0a465b1100';

    private function getGroqKey(): string
    {
        return $_ENV['GROQ_API_KEY'] ?? self::GROQ_API_KEY;
    }

    #[Route('/translate', name: 'translate', methods: ['POST'])]
    public function translate(Request $request): JsonResponse
    {
        $data   = json_decode($request->getContent(), true);
        $texte  = $data['texte'] ?? '';
        $lang   = $data['lang'] ?? 'fr-fr';

        if (empty($texte)) {
            return new JsonResponse(['error' => 'Texte vide'], 400);
        }

        if ($lang === 'fr-fr') {
            return new JsonResponse(['translated' => $texte]);
        }

        $langpair = $lang === 'ar-sa' ? 'fr|ar' : 'fr|en';
        $url = 'https://api.mymemory.translated.net/get?q='
            . urlencode($texte)
            . '&langpair=' . $langpair
            . '&de=ranimoudrani@gmail.com';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $json = json_decode($response, true);
            $translated = $json['responseData']['translatedText'] ?? null;
            if ($translated && !str_starts_with($translated, 'MYMEMORY')) {
                return new JsonResponse(['translated' => $translated]);
            }
        }

        return new JsonResponse(['error' => 'Traduction échouée'], 500);
    }

    #[Route('/presentation/translate-voice', name: 'presentation_translate_voice', methods: ['POST'])]
    public function translateVoice(Request $request): JsonResponse
    {
        $data  = json_decode($request->getContent(), true);
        $texte = $data['texte'] ?? '';
        $lang  = $data['lang'] ?? 'fr-fr';

        if (empty($texte)) return new JsonResponse(['error' => 'Texte vide'], 400);

        // 1. Traduction si nécessaire
        $translated = $texte;
        if ($lang !== 'fr-fr') {
            $langpair = $lang === 'ar-sa' ? 'fr|ar' : 'fr|en';
            $url = 'https://api.mymemory.translated.net/get?q='
                . urlencode(mb_substr($texte, 0, 500))
                . '&langpair=' . $langpair
                . '&de=ranimoudrani@gmail.com';

            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false]);
            $resp = curl_exec($ch); curl_close($ch);
            $json = json_decode($resp, true);
            $t = $json['responseData']['translatedText'] ?? null;
            if ($t && !str_starts_with($t, 'MYMEMORY')) $translated = $t;
        }

        // 2. Audio VoiceRSS
        $audioBase64 = $this->downloadTTSBase64($translated, $lang);

        return new JsonResponse([
            'translated'  => $translated,
            'audioBase64' => $audioBase64,
        ]);
    }

    #[Route('/presentation/generate', name: 'presentation_generate', methods: ['POST'])]
    public function generate(Request $request): JsonResponse
    {
        $data   = json_decode($request->getContent(), true);
        $contenu = $data['contenu'] ?? '';
        $titre   = $data['titre'] ?? 'Module';
        $lang    = $data['lang'] ?? 'fr-fr';

        if (empty($contenu)) {
            return new JsonResponse(['error' => 'Contenu vide'], 400);
        }

        try {
            // 1. Enrichir avec Groq IA
            $explication = $this->enrichirAvecGroq($contenu, $lang);

            // 2. Télécharger audio VoiceRSS
            $audioBase64 = $this->downloadTTSBase64($explication, $lang);

            // 3. Construire le HTML de présentation
            $html = $this->buildPresentationHTML($titre, $explication, $audioBase64, $lang, $contenu, $this->generateUrl('client_presentation_translate_voice'));

            return new JsonResponse(['html' => $html]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function enrichirAvecGroq(string $contenu, string $lang): string
    {
        $prompt = match ($lang) {
            'ar-sa' => "أنت أستاذ خبير. اشرح هذا المحتوى بالعربية مع أمثلة عملية. الحد الأقصى 200 كلمة.\nالمحتوى: " . $contenu,
            'en-us' => "You are an expert teacher. Explain this content in English with practical examples. Max 200 words.\nContent: " . $contenu,
            default => "Tu es un professeur expert. Explique ce contenu en français avec des exemples pratiques. Max 200 mots.\nContenu: " . $contenu,
        };

        $payload = json_encode([
            'model'      => 'llama-3.3-70b-versatile',
            'messages'   => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 400,
        ]);

        $ch = curl_init(self::GROQ_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getGroqKey(),
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $json = json_decode($response, true);
            return $json['choices'][0]['message']['content'] ?? $contenu;
        }

        return $contenu;
    }

    private function downloadTTSBase64(string $texte, string $lang): ?string
    {
        $texte = mb_substr($texte, 0, 500);
        $params = http_build_query([
            'key' => self::VOICERSS_KEY,
            'hl'  => $lang,
            'src' => $texte,
            'c'   => 'MP3',
            'f'   => '16khz_16bit_stereo',
        ]);

        $ch = curl_init('https://api.voicerss.org/');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $params,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $audio = curl_exec($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && strlen($audio) > 1000 && !str_starts_with($audio, 'ERROR')) {
            return 'data:audio/mpeg;base64,' . base64_encode($audio);
        }

        return null;
    }

    private function buildPresentationHTML(string $titre, string $texte, ?string $audioBase64, string $lang, string $texteOriginal = '', string $translateVoiceUrl = ''): string
    {
        $phrases = preg_split('/(?<=[.!?])\s+/', $texte);
        $liItems = '';
        foreach ($phrases as $phrase) {
            $phrase = trim($phrase);
            if ($phrase) {
                $liItems .= '<li>' . htmlspecialchars($phrase) . '</li>';
            }
        }

        $dir      = $lang === 'ar-sa' ? 'rtl' : 'ltr';
        $audioTag = $audioBase64
            ? "<audio id='mainAudio' autoplay><source src='{$audioBase64}' type='audio/mpeg'></audio>"
            : '';

        $texteOriginalJs = json_encode($texteOriginal ?: $texte);
        $translateVoiceUrlJs = json_encode($translateVoiceUrl);

        return <<<HTML
<!DOCTYPE html>
<html lang="fr" dir="{$dir}">
<head>
<meta charset="UTF-8">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;padding:30px}
.container{max-width:860px;width:100%}
.avatar-zone{display:flex;align-items:center;gap:20px;margin-bottom:28px}
.avatar{width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-size:42px;flex-shrink:0;box-shadow:0 0 30px rgba(102,126,234,0.6);animation:pulse 2s ease-in-out infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 30px rgba(102,126,234,0.6);transform:scale(1)}50%{box-shadow:0 0 50px rgba(102,126,234,0.9);transform:scale(1.05)}}
h1{font-size:2em;font-weight:700;background:linear-gradient(90deg,#a78bfa,#60a5fa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:6px}
.subtitle{color:#a0aec0;font-size:.95em}
.badge{display:inline-block;background:rgba(102,126,234,.2);border:1px solid rgba(102,126,234,.4);color:#a78bfa;padding:3px 12px;border-radius:50px;font-size:.78em;margin-bottom:10px}
.progress-bar{width:100%;height:5px;background:rgba(255,255,255,.1);border-radius:3px;margin-bottom:22px;overflow:hidden}
.progress-fill{height:100%;background:linear-gradient(90deg,#667eea,#a78bfa);border-radius:3px;width:0%;transition:width .4s ease}
.card{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:30px;backdrop-filter:blur(10px);margin-bottom:22px}
ul{list-style:none}
li{padding:10px 0;border-bottom:1px solid rgba(255,255,255,.08);font-size:1.08em;line-height:1.7;color:#e2e8f0;opacity:0;transform:translateY(18px);transition:opacity .5s ease,transform .5s ease}
li:last-child{border-bottom:none}
li.visible{opacity:1;transform:translateY(0)}
li::before{content:'▸ ';color:#a78bfa}
.controls{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
button{padding:11px 26px;border:none;border-radius:50px;font-size:.95em;cursor:pointer;font-weight:600;transition:all .2s}
.btn-play{background:linear-gradient(135deg,#667eea,#764ba2);color:white}
.btn-play:hover{transform:scale(1.05);box-shadow:0 4px 20px rgba(102,126,234,.5)}
.btn-lang{background:rgba(167,139,250,.15);color:#a78bfa;border:1px solid rgba(167,139,250,.3);font-size:.85em;padding:8px 16px}
.btn-lang:hover{background:rgba(167,139,250,.3);transform:scale(1.05)}
.btn-restart{background:rgba(255,255,255,.1);color:white;border:1px solid rgba(255,255,255,.2)}
.btn-restart:hover{background:rgba(255,255,255,.2)}
.btn-stop{background:rgba(226,75,74,.2);color:#E24B4A;border:1px solid rgba(226,75,74,.4)}
.btn-stop:hover{background:rgba(226,75,74,.35);transform:scale(1.05)}
.audio-status{color:#a78bfa;font-size:.85em;text-align:center;margin-bottom:10px;min-height:20px}
</style>
</head>
<body>
{$audioTag}
<div class="container">
  <div class="avatar-zone">
    <div class="avatar">🎓</div>
    <div>
      <span class="badge">IA Pédagogique</span>
      <h1>{$titre}</h1>
      <p class="subtitle">Explication générée par intelligence artificielle</p>
    </div>
  </div>
  <div class="progress-bar"><div class="progress-fill" id="pf"></div></div>
  <p class="audio-status" id="audioStatus">🔊 Chargement audio...</p>
  <div class="card"><ul id="list">{$liItems}</ul></div>
  <div class="controls">
    <button class="btn-lang" onclick="changeLang('ar-sa')">🇸🇦 Arabe</button>
    <button class="btn-lang" onclick="changeLang('fr-fr')">🇫🇷 Français</button>
    <button class="btn-lang" onclick="changeLang('en-us')">🇬🇧 Anglais</button>
    <button class="btn-play" id="btnToggle" onclick="toggleAudio()">⏸ Pause</button>
    <button class="btn-restart" onclick="restart()">↩ Recommencer</button>
    <button class="btn-stop" onclick="stopAndClose()">⏹ Arrêter et quitter</button>
  </div>
</div>
<script>
var items=document.querySelectorAll('#list li');
var pf=document.getElementById('pf');
var statusEl=document.getElementById('audioStatus');
var btnToggle=document.getElementById('btnToggle');
var idx=0,timer=null;
function showNext(){if(idx<items.length){items[idx].classList.add('visible');idx++;pf.style.width=(idx/items.length*100)+'%';}else{clearInterval(timer);}}
function start(){idx=0;items.forEach(function(i){i.classList.remove('visible');});pf.style.width='0%';clearInterval(timer);timer=setInterval(showNext,3000);showNext();}
var audio=document.getElementById('mainAudio');
if(audio){
  audio.addEventListener('canplay',function(){statusEl.textContent='🔊 Audio prêt';setTimeout(function(){statusEl.textContent='';},2000);});
  audio.addEventListener('playing',function(){statusEl.textContent='🔊 Lecture en cours...';btnToggle.innerHTML='⏸ Pause';});
  audio.addEventListener('pause',function(){statusEl.textContent='';btnToggle.innerHTML='▶ Reprendre';});
  audio.addEventListener('ended',function(){statusEl.textContent='✅ Terminé';btnToggle.innerHTML='▶ Rejouer';});
  audio.addEventListener('error',function(){statusEl.textContent='⚠ Audio non disponible';});
}else{statusEl.textContent='';}
function toggleAudio(){if(!audio)return;if(audio.paused){audio.play();}else{audio.pause();}}
function restart(){if(audio){audio.currentTime=0;audio.play();}start();}
function stopAndClose(){clearInterval(timer);if(audio){audio.pause();audio.currentTime=0;}window.parent.closePresentation();}
var ORIGINAL_TEXTE={$texteOriginalJs};
var TRANSLATE_URL={$translateVoiceUrlJs};
function changeLang(lang){
  statusEl.textContent='⏳ Traduction en cours...';
  clearInterval(timer);
  if(audio){audio.pause();}
  fetch(TRANSLATE_URL,{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({texte:ORIGINAL_TEXTE,lang:lang})
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.error){statusEl.textContent='⚠ '+data.error;return;}
    // Mettre à jour les phrases
    var phrases=data.translated.split(/(?<=[.!?])\s+/).filter(p=>p.trim());
    var ul=document.getElementById('list');
    ul.innerHTML='';
    phrases.forEach(function(p){
      var li=document.createElement('li');
      li.textContent=p;
      ul.appendChild(li);
    });
    items=document.querySelectorAll('#list li');
    // Mettre à jour l'audio
    if(data.audioBase64){
      if(audio){audio.pause();}
      audio=document.getElementById('mainAudio');
      if(!audio){audio=document.createElement('audio');audio.id='mainAudio';document.body.appendChild(audio);}
      audio.src=data.audioBase64;
      audio.load();
      audio.play().catch(()=>{});
    }
    statusEl.textContent='';
    start();
  })
  .catch(()=>{statusEl.textContent='⚠ Erreur réseau';});
}
window.onload=function(){setTimeout(start,500);};
</script>
</body>
</html>
HTML;
    }
}
