$(document).ready(function(){

    function parseTimeToMinutes(timeStr){
        const [h,m] = timeStr.split(":").map(Number);
        return h*60 + m;
    }

    function getNextSpawn(timeStr, intervalMinutes){
        const now = new Date();
        const nowMinutes = now.getHours()*60 + now.getMinutes();
        const nowSeconds = now.getSeconds();
        const startMinutes = parseTimeToMinutes(timeStr);

        let elapsed = (nowMinutes - startMinutes + 1440) % intervalMinutes;
        let remainingMinutes = intervalMinutes - elapsed;
        let remainingSeconds = 59 - nowSeconds;

        if(remainingMinutes === intervalMinutes && remainingSeconds === 59){
            remainingMinutes = 0;
            remainingSeconds = 0;
        }

        const nextSpawnTime = new Date(now.getTime() + remainingMinutes*60000 + remainingSeconds*1000);
        return { remainingMinutes, remainingSeconds, nextSpawnTime };
    }

    function loadBosses(){
        $.getJSON('api.php', function(data){
            const container = $('#bossCards');
            container.empty();

            data.forEach((boss,index)=>{
                const next = getNextSpawn(boss.start_time, parseInt(boss.interval));
                const mins = next.remainingMinutes;
                const secs = next.remainingSeconds;

                // Renk mantığı
                let colorClass = "green";
                const totalSec = mins*60 + secs;
                if(totalSec <= 60) colorClass = "red";       // son 1 dk
                else if(totalSec <= 180) colorClass = "yellow"; // son 3 dk
                else if(totalSec <= 300) colorClass = "green";  // son 5 dk

                const card = $(`
                    <div class="col-md-4">
                        <div class="boss-card">
                            <div class="boss-name">${boss.name}</div>
                            <div>Sonraki Spawn: ${next.nextSpawnTime.toTimeString().substring(0,5)}</div>
                            <div class="time-remaining ${colorClass}">${mins}dk ${secs}sn</div>
                            <button class="btn btn-danger btn-sm delete-btn" data-index="${index}">Sil</button>
                        </div>
                    </div>
                `);
                container.append(card);
            });
        });
    }

    $('#addBoss').click(function(){
        const name = $('#bossName').val();
        const interval = $('#interval').val();
        const start_time = $('#startTime').val();

        if(!name || !interval || !start_time){
            alert('Lütfen tüm alanları doldurun!');
            return;
        }

        $.post('api.php',{action:'add', name, interval, start_time}, function(){
            loadBosses();
            $('#bossName').val('');
            $('#interval').val('');
            $('#startTime').val('');
        }).fail(function(){
            alert('Boss eklenemedi!');
        });
    });

    $(document).on('click','.delete-btn', function(){
        const index = $(this).data('index');
        $.post('api.php',{action:'delete', index}, function(){
            loadBosses();
        });
    });

    loadBosses();
    setInterval(loadBosses,1000);
});
