// Match the ambient photographic lighting to the selected day/night mood.
document.addEventListener('timechange',event=>{
 document.documentElement.dataset.mood=event.detail;
});
