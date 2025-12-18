<?php
require_once 'partials/header.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$activeConvId = $_GET['conv_id'] ?? 0;
$myId = $_SESSION['user']['id'];
?>

<title>Mes discussions - ColoMap</title>

<style>
    /* Scrollbar personnalisée pour un look plus net */
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background: #0A112F; }
</style>

<div id="context-menu" class="fixed hidden bg-white shadow-2xl rounded-lg w-48 py-2 z-[100] border border-gray-200 text-sm font-medium transform transition-all duration-100 scale-95 origin-top-left">
    <div class="px-4 py-2 text-xs text-gray-400 uppercase tracking-wider font-bold border-b border-gray-100 mb-1">Actions</div>
    <button id="ctx-edit" class="w-full text-left px-4 py-2.5 hover:bg-blue-50 text-[#0A112F] flex items-center gap-3 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Modifier le message
    </button>
    <button id="ctx-delete" class="w-full text-left px-4 py-2.5 hover:bg-red-50 text-red-600 flex items-center gap-3 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        Supprimer
    </button>
</div>

<div class="flex h-[calc(100vh-80px)] bg-gray-100 font-sans overflow-hidden">
    
    <div id="sidebar" class="w-full md:w-[380px] bg-white border-r-2 border-gray-200 flex flex-col flex-shrink-0 z-10 <?= $activeConvId ? 'hidden md:flex' : 'flex' ?>">
        
        <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-white shadow-sm z-10">
            <div>
                <h2 class="text-2xl font-extrabold text-[#0A112F] tracking-tight">Discussions</h2>
                <p class="text-xs text-gray-500 mt-1">Vos échanges récents</p>
            </div>
        </div>

        <div id="conv-list" class="flex-1 overflow-y-auto p-4 space-y-3 custom-scroll bg-gray-50">
            <div class="p-10 text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#0A112F] mx-auto"></div>
            </div>
        </div>
    </div>

    <div id="chat-area" class="flex-1 flex flex-col bg-[#E5E7EB] relative <?= $activeConvId ? 'flex' : 'hidden md:flex' ?>">
        
        <?php if (!$activeConvId): ?>
            <div class="flex-1 flex flex-col items-center justify-center text-center p-8">
                <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center mb-6 shadow-md">
                    <svg class="w-16 h-16 text-[#0A112F] opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                </div>
                <h3 class="text-2xl font-bold text-[#0A112F]">Vos Messages</h3>
                <p class="text-gray-500 mt-2 max-w-sm">Sélectionnez une conversation à gauche pour afficher l'historique.</p>
            </div>
        <?php else: ?>
            
            <div class="bg-white px-6 py-3 border-b border-gray-300 shadow-sm flex items-center justify-between z-20">
                <div class="flex items-center gap-4">
                    <a href="messagerie" class="md:hidden p-2 -ml-2 text-gray-600 hover:bg-gray-100 rounded-full transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </a>
                    
                    <div class="relative">
                        <div class="h-12 w-12 rounded-full bg-[#0A112F] text-white flex items-center justify-center font-bold text-xl shadow-md border-2 border-white" id="header-avatar">?</div>
                    </div>
                    
                    <div>
                        <h3 class="font-bold text-[#0A112F] text-lg leading-tight" id="header-camp">...</h3>
                        <p class="text-sm text-gray-500 font-medium" id="header-contact">...</p>
                    </div>
                </div>
            </div>

            <div id="messages-container" class="flex-1 overflow-y-auto p-6 space-y-4 custom-scroll scroll-smooth">
                </div>

            <div class="bg-white p-4 border-t border-gray-300 z-20">
                <form id="msg-form" class="max-w-5xl mx-auto relative flex items-center gap-3">
                    <input type="text" id="msg-input" 
                           class="w-full bg-gray-100 text-gray-900 border border-gray-200 rounded-full py-4 pl-6 pr-14 focus:outline-none focus:ring-2 focus:ring-[#0A112F] focus:bg-white transition-all shadow-inner placeholder-gray-500 font-medium" 
                           placeholder="Écrivez votre message..." autocomplete="off">
                    
                    <button type="submit" class="absolute right-2 top-2 p-2 bg-[#0A112F] text-white rounded-full hover:bg-blue-900 transition-all active:scale-95 shadow-lg flex items-center justify-center h-10 w-10 group">
                        <svg class="w-5 h-5 group-hover:translate-x-0.5 transition-transform transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </form>
            </div>

        <?php endif; ?>
    </div>
</div>

<script>
const myId = <?= $myId ?>;
const activeConvId = <?= $activeConvId ?>;
let pollingInterval = null;
let contextMessageId = null;

document.addEventListener('DOMContentLoaded', () => {
    loadConversations();
    
    if (activeConvId) {
        loadMessages();
        pollingInterval = setInterval(loadMessages, 3000);
    }

    // Gestion Envoi
    const form = document.getElementById('msg-form');
    if(form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('msg-input');
            const content = input.value.trim();
            if(!content) return;

            // Ajout visuel immédiat
            appendMessage({
                id: 'temp-' + Date.now(),
                content: content,
                sender_id: myId,
                created_at: new Date().toISOString()
            }, true);
            
            input.value = '';

            try {
                await fetch('api/send_message.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ conv_id: activeConvId, content: content })
                });
                loadConversations(); 
            } catch(e) { console.error(e); }
        });
    }

    // CLIC DROIT
    const menu = document.getElementById('context-menu');
    const deleteBtn = document.getElementById('ctx-delete');
    const editBtn = document.getElementById('ctx-edit');

    document.addEventListener('click', () => {
        menu.classList.add('hidden');
        menu.classList.remove('scale-100');
        menu.classList.add('scale-95');
    });

    // Supprimer
    deleteBtn.addEventListener('click', async () => {
        if(!contextMessageId) return;
        if(confirm('Voulez-vous vraiment supprimer ce message ?')) {
            try {
                await fetch('api/delete_message.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ message_id: contextMessageId })
                });
                // Suppression visuelle immédiate
                const el = document.getElementById(`msg-${contextMessageId}`);
                if(el) {
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 300);
                }
            } catch(e) {}
        }
    });

    // Modifier
    editBtn.addEventListener('click', async () => {
        if(!contextMessageId) return;
        const msgDiv = document.querySelector(`#msg-${contextMessageId} .msg-content`);
        const oldText = msgDiv.innerText;
        const newText = prompt("Modifier votre message :", oldText);
        
        if(newText && newText !== oldText) {
            try {
                await fetch('api/edit_message.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ message_id: contextMessageId, content: newText })
                });
                msgDiv.innerText = newText;
                // Petit effet visuel
                msgDiv.classList.add('bg-blue-800');
                setTimeout(() => msgDiv.classList.remove('bg-blue-800'), 500);
            } catch(e) {}
        }
    });
});

// 1. LISTE CONVERSATIONS (STYLE CARTES HAUTE VISIBILITÉ)
async function loadConversations() {
    try {
        const res = await fetch('api/get_conversations.php');
        const convs = await res.json();
        const list = document.getElementById('conv-list');
        list.innerHTML = '';

        if(!convs || convs.length === 0) {
            list.innerHTML = '<div class="p-8 text-center text-gray-400 text-sm">Aucune discussion.</div>';
            return;
        }

        convs.forEach(c => {
            const isActive = (c.conversation_id == activeConvId);
            
            if(isActive) {
                document.getElementById('header-camp').textContent = c.camp_nom || 'Discussion';
                document.getElementById('header-contact').textContent = `${c.contact_prenom} ${c.contact_nom}`;
                document.getElementById('header-avatar').textContent = (c.contact_prenom || '?').charAt(0).toUpperCase();
            }

            const div = document.createElement('a');
            div.href = `messagerie?conv_id=${c.conversation_id}`;
            
            // STYLE ACTIF : Fond #0A112F, Texte Blanc
            // STYLE INACTIF : Fond Blanc, Bordure, Ombre légère
            if (isActive) {
                div.className = "block p-4 mb-2 rounded-xl bg-[#0A112F] text-white shadow-lg border-l-4 border-blue-400 transform scale-[1.02] transition-all duration-200";
            } else {
                div.className = "block p-4 mb-2 rounded-xl bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:shadow-md transition-all duration-200";
            }
            
            const isUnread = (c.last_sender_id != myId && c.last_is_read == 0);
            const titleColor = isActive ? 'text-white' : 'text-[#0A112F]';
            const subColor = isActive ? 'text-blue-200' : 'text-gray-500';
            const msgColor = isActive ? 'text-gray-300' : (isUnread ? 'text-gray-900 font-bold' : 'text-gray-500');

            div.innerHTML = `
                <div class="flex justify-between items-start mb-1">
                    <span class="font-bold ${titleColor} text-sm truncate pr-2 uppercase tracking-wide">${c.camp_nom}</span>
                    ${isUnread ? '<span class="h-2.5 w-2.5 bg-red-600 rounded-full border border-white animate-pulse"></span>' : ''}
                </div>
                <div class="text-xs ${subColor} font-medium mb-2">${c.contact_prenom} ${c.contact_nom}</div>
                <p class="text-sm truncate ${msgColor} flex items-center gap-1">
                    ${c.last_sender_id == myId ? '<svg class="w-3 h-3 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>' : ''}
                    ${c.last_message || '...'}
                </p>
            `;
            list.appendChild(div);
        });
    } catch(e) {}
}

// 2. MESSAGES
async function loadMessages() {
    try {
        const res = await fetch(`api/get_messages.php?conv_id=${activeConvId}`);
        if(res.status === 403) { window.location.href='messagerie.php'; return; }
        const msgs = await res.json();
        
        const container = document.getElementById('messages-container');
        if(container.childElementCount === msgs.length) return; 

        container.innerHTML = '';
        msgs.forEach(m => appendMessage(m, false));
        container.scrollTop = container.scrollHeight;
        
        await fetch('api/mark_messages_read.php', { method: 'POST', body: JSON.stringify({ conv_id: activeConvId }) });
    } catch(e) {}
}

// 3. RENDU MESSAGE (AVEC CORRECTION DU MENU CLIC DROIT)
function appendMessage(m, forceScroll) {
    const container = document.getElementById('messages-container');
    const isMe = (m.sender_id == myId);
    
    const wrapper = document.createElement('div');
    wrapper.id = `msg-${m.id}`;
    wrapper.className = `flex w-full ${isMe ? 'justify-end' : 'justify-start'} group mb-2`;
    
    // EVENEMENT CLIC DROIT CORRIGÉ
    if(isMe) {
        wrapper.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            contextMessageId = m.id;
            const menu = document.getElementById('context-menu');
            
            // 1. On affiche le menu (mais transparent) pour pouvoir calculer sa taille réelle
            menu.style.opacity = '0';
            menu.classList.remove('hidden');
            
            // 2. Récupération des dimensions
            const menuWidth = menu.offsetWidth;
            const windowWidth = window.innerWidth;
            
            // 3. Calcul intelligent de la position X
            let x = e.clientX;
            
            // Si la souris est trop à droite (souris + menu > largeur écran), on affiche à GAUCHE
            if (x + menuWidth > windowWidth) {
                x = x - menuWidth; 
            }
            
            // 4. Calcul intelligent de la position Y (optionnel, pour ne pas sortir en bas)
            const menuHeight = menu.offsetHeight;
            const windowHeight = window.innerHeight;
            let y = e.clientY;
            
            if (y + menuHeight > windowHeight) {
                y = y - menuHeight;
            }

            // 5. Application
            menu.style.top = `${y}px`;
            menu.style.left = `${x}px`;
            
            // 6. Animation d'apparition
            setTimeout(() => {
                menu.style.opacity = '1';
                menu.classList.remove('scale-95');
                menu.classList.add('scale-100');
            }, 10);
        });
    }

    // DESIGN BULLES (Inchangé)
    const bubbleClass = isMe 
        ? 'bg-[#0A112F] text-white rounded-2xl rounded-tr-sm shadow-md' 
        : 'bg-white text-gray-800 border border-gray-300 rounded-2xl rounded-tl-sm shadow-sm';

    const time = new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

    wrapper.innerHTML = `
        <div class="max-w-[75%] md:max-w-[65%] flex flex-col ${isMe ? 'items-end' : 'items-start'}">
            <div class="${bubbleClass} px-5 py-3 relative text-[15px] leading-relaxed msg-content break-words">
                ${m.content}
            </div>
            <span class="text-[10px] text-gray-500 mt-1 px-1 font-medium select-none">
                ${time}
            </span>
        </div>
    `;
    container.appendChild(wrapper);
    if(forceScroll) container.scrollTop = container.scrollHeight;
}
</script>
</body>
</html>