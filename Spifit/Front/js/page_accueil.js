document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Add your logout functionality here
            window.location.href = 'loginc.php';
        });
    }
});

document.querySelector("form").addEventListener("submit", function(event) {
    event.preventDefault();

    let size = parseFloat(document.getElementById("size").value);
    let weight = parseFloat(document.getElementById("weight").value);
    let age = parseInt(document.getElementById("age").value);
    let gender = document.querySelector('input[name="gender"]:checked')?.value;
    let activity = document.querySelector('input[name="activity"]:checked')?.value;

    if (isNaN(size) || isNaN(weight) || isNaN(age) || !gender || !activity) {
        alert("Please fill in all fields correctly.");
        return;
    }

    // Calculate BMR based on gender
    let bmr;
    if (gender === "man") {
        bmr = 88.36 + (13.4 * weight) + (4.8 * size) - (5.7 * age);
    } else {
        bmr = 447.6 + (9.2 * weight) + (3.1 * size) - (4.3 * age);
    }

    // Adjust based on activity level
    let calorieNeeds;
    if (activity === "lose_weight") {
        calorieNeeds = bmr * 0.8; // 20% calorie reduction for weight loss
    } else {
        calorieNeeds = bmr * 1.2; // 20% calorie surplus for muscle gain
    }

    alert(`Your estimated daily calorie needs: ${Math.round(calorieNeeds)} kcal`);
});
