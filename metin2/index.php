<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Metin2 Boss Takip</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet">
</head>
<body class="bg-light text-dark">
<div class="container py-3">

    <h1 class="mb-3 text-center fw-light">Metin2 Boss Takip</h1>

    <div class="d-flex justify-content-center mb-2">
        <button class="btn btn-outline-dark btn-sm" id="toggleForm">Boss Ekle</button>
    </div>

    <div class="boss-form mb-3 p-3 border rounded" id="bossForm" style="display:none;">
        <div class="row g-2">
            <div class="col-12 col-md-4">
                <input type="text" id="bossName" class="form-control form-control-sm" placeholder="Boss İsmi">
            </div>
            <div class="col-6 col-md-4">
                <input type="number" id="interval" class="form-control form-control-sm" placeholder="Interval (dk)">
            </div>
            <div class="col-6 col-md-4">
                <input type="time" id="startTime" class="form-control form-control-sm" placeholder="Başlangıç Saati">
            </div>
        </div>
        <div class="mt-2 text-end">
            <button class="btn btn-dark btn-sm" id="addBoss">Ekle</button>
        </div>
    </div>

    <div class="row" id="bossCards">
        <!-- Boss cardları buraya eklenecek -->
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assets/script.js"></script>
<script>
$('#toggleForm').click(function(){
    $('#bossForm').slideToggle();
});
</script>
</body>
</html>