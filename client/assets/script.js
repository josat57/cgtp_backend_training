
const register = () => {
    fetch('http://localhost:8888/cgtp.com/api/index.php/auth/register', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        Type: JSON,
        body: JSON.stringify({userId: 10, name: 'John', age: 30 })
      })
        .then(response => response.json())
        .then(data => {
          console.log('Success:', data);
        })
        .catch(error => {
          console.error('Error:', error);
        });
}

register();
  