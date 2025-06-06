select
	d.year,
	d.month,
	d.month_name,
	count(*) as total_flights
from flights f
join dates d on f.departure_date_id = d.date_id
join flight_statuses s on f.flight_status_id = s.status_id
where s.name != 'Cancelled'
group by d.year, d.month, d.month_name
order by d.year, d.month