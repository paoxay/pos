<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <title>Export Product JSON</title>
</head>
<body>

    <h2>ກຳລັງປະມວນຜົນຂໍ້ມູນ...</h2>
    <button onclick="fetchAndDownload()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">
        ຄິກເພື່ອດາວໂຫຼດ JSON ໃໝ່ອີກຄັ້ງ
    </button>
    <pre id="output" style="background:#f4f4f4; padding:10px; margin-top:20px;"></pre>

    <script>
        const apiUrl = "https://apipos.devla.la/api/products_all?cat_id=0&filter=all_product&user_id=155&branch_id=49&store_id=45&round_up=0&page=1&per_page=499";
        const apiToken = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9hcGlwb3MuZGV2bGEubGFcL2FwaVwvbG9naW4iLCJpYXQiOjE3Njc2MjIzODgsImV4cCI6MTc2NzcwODc4OCwibmJmIjoxNzY3NjIyMzg4LCJqdGkiOiI3cmJVRk1DeTdCUTE1U01tIiwic3ViIjoxNTUsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.eHKCH6S4p3FkW5DLty0xtBqXKfbntBj9jhwgeSzGGCY";

        async function fetchAndDownload() {
            try {
                const response = await fetch(apiUrl, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${apiToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const json = await response.json();

                if (json.status === true) {
                    // ແປງຂໍ້ມູນໃຫ້ກົງກັບ Format ທີ່ຕ້ອງການ (Mapping)
                    const formattedData = json.data.data.map(item => ({
                        "barcode": item.code,
                        "name": item.name,
                        "stock": Number(item.stock),      // ແປງເປັນຕົວເລກ
                        "cost": Number(item.price_buy),   // ແປງເປັນຕົວເລກ
                        "price": Number(item.price_sale)  // ແປງເປັນຕົວເລກ
                    }));

                    // ສະແດງຕົວຢ່າງໜ້າເວັບ
                    document.getElementById("output").textContent = JSON.stringify(formattedData.slice(0, 3), null, 4) + "\n... (ແລະລາຍການອື່ນໆ)";

                    // ສ້າງໄຟລ໌ JSON ເພື່ອດາວໂຫຼດ
                    downloadFile(formattedData, "products_export.json");
                }
            } catch (error) {
                console.error("Error:", error);
                alert("ເກີດຂໍ້ຜິດພາດໃນການດຶງຂໍ້ມູນ");
            }
        }

        function downloadFile(data, filename) {
            const blob = new Blob([JSON.stringify(data, null, 4)], { type: "application/json" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        // ເຮັດວຽກທັນທີເມື່ອເປີດ
        fetchAndDownload();
    </script>

</body>
</html>